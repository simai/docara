<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use JsonException;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeCompiler;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\SiteTheme;
use Symfony\Component\Process\Process;

/**
 * A site theme computed by the pinned SIMAI Framework's theme palette.
 *
 * The Framework makes a theme from one colour (SF.ThemePalette,
 * core/js/theme-palette.js, on Google's HCT): four tonal palettes (primary,
 * secondary, tertiary, neutral) of sixteen tones, the 64
 * `--sf-<palette>-<tone>` primitives every semantic token reads through
 * light-dark(). From ui-994f9db407fa the four status palettes are the static
 * hues core declares and no theme moves (TG-01); releases up to
 * ui-7fb26d395af2 wrote them too, eight palettes in all. Docara does not reimplement it. It runs the release's
 * own bytes -- the sf-theme-generator entrypoint of the bundled Smart
 * projection, checked against the lock's SHA-256 -- with the Node.js binary
 * that already runs the Composition Recipe runtime, and takes `themeToCss`'s
 * rule as the site's stylesheet.
 *
 * The result is checked before it is used: exactly the four palettes and
 * sixteen tones, each a #rrggbb value, and the CSS byte-equal to the rule
 * those variables make. So the stylesheet can hold nothing but the theme.
 */
final class FrameworkThemePalette
{
    public const RUNTIME_PATH = 'smart/theme-generator/js/theme-generator.js';

    public const PALETTES = ['primary', 'secondary', 'tertiary', 'neutral'];

    public const TONES = [5, 10, 15, 20, 25, 30, 35, 40, 50, 60, 70, 80, 85, 90, 95, 98];

    private readonly string $adapter;

    public function __construct(private readonly string $nodeBinary, ?string $adapter = null)
    {
        $this->adapter = $adapter ?? dirname(__DIR__, 2) . '/resources/runtime/theme-palette-adapter.mjs';
    }

    /**
     * The site's theme stylesheet and its receipt.
     *
     * @param  array<string, mixed>  $frameworkLock  the project lock (a superseded lock resolves to the bundled release)
     * @return array{content: string, receipt: array<string, mixed>}
     */
    public function stylesheet(array $frameworkLock, SiteTheme $theme): array
    {
        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($frameworkLock));
        $pair = (string) ($repository->runtime()['pair_id'] ?? '');
        try {
            $source = $repository->bundledAsset(self::RUNTIME_PATH);
        } catch (FrameworkComponentException $exception) {
            throw new PortableConfigurationException(
                'THEME_FRAMEWORK_UNSUPPORTED',
                "docara.json sets \"theme\", but the SIMAI Framework release [$pair] has no theme palette"
                    . ' (sf-theme-generator, release ui-45384c434d3f or later). Replace simai-framework.lock.json'
                    . ' with vendor/simai/docara/stubs/portable/simai-framework.lock.json.',
                $exception,
            );
        }
        $modules = self::sharedModules();
        $content = '';
        $palette = null;
        if ($theme->color !== null) {
            $main = $this->palette($source, $modules, $theme->input());
            $palette = $main['palette'];
            $content = '/* docara-theme: ' . $theme->color . ' (hue ' . $palette['hue'] . ', chroma ' . $palette['chroma']
                . '), SIMAI Framework ' . $pair . ' SF.ThemePalette.themeToCss */' . "\n" . $main['css'];
            $count = $main['count'];
        }
        // A reader's colour choice: the same palette, under the root marked
        // with the choice, after the site's own rule so it wins.
        $choices = [];
        foreach ($theme->choices as $choice) {
            $made = $this->palette($source, $modules, [
                'color' => $choice['color'],
                'hue' => $choice['hue'],
                'chroma' => $choice['chroma'],
            ]);
            $selector = ':root[data-docara-color="' . $choice['id'] . '"]';
            $content .= '/* docara-theme choice ' . $choice['id'] . ': ' . $choice['color']
                . ' (hue ' . $made['palette']['hue'] . ', chroma ' . $made['palette']['chroma'] . ') */' . "\n"
                . $selector . substr($made['css'], strlen(':root'));
            $choices[] = ['id' => $choice['id'], ...$made['palette']];
            $count ??= $made['count'];
        }
        $sha256 = hash('sha256', $content);

        return [
            'content' => $content,
            'receipt' => [
                'schema' => SiteTheme::SCHEMA,
                'input' => $theme->input(),
                'palette' => $palette,
                ...($choices === [] ? [] : ['choices' => $choices]),
                'framework' => [
                    'pair_id' => $pair,
                    'runtime' => self::RUNTIME_PATH,
                    'sha256' => hash('sha256', $source),
                ],
                'stylesheet' => [
                    'filename' => 'docara-theme.' . $sha256 . '.css',
                    'sha256' => $sha256,
                    'variables' => $count ?? 0,
                ],
            ],
        ];
    }

    /**
     * One colour through the Framework palette, checked.
     *
     * @param  array<string, string>  $modules
     * @param  array{color: string|null, hue: int|null, chroma: int|null}  $input
     * @return array{css: string, palette: array{hue: int, chroma: int}, count: int}
     */
    private function palette(string $source, array $modules, array $input): array
    {
        $result = $this->run($source, $modules, $input);
        $variables = self::checkedVariables($result['variables'] ?? null);
        $css = $result['css'] ?? null;
        if (! is_string($css) || $css !== self::rule($variables)) {
            throw new PortableConfigurationException(
                'THEME_PALETTE_RESULT_INVALID',
                'The SIMAI Framework theme palette returned a stylesheet that is not its variables.',
            );
        }
        $read = is_array($result['source'] ?? null) ? $result['source'] : [];
        if (! is_string($read['hex'] ?? null)
            || ! is_int($read['hue'] ?? null)
            || ! is_int($read['chroma'] ?? null)
        ) {
            throw new PortableConfigurationException(
                'THEME_PALETTE_RESULT_INVALID',
                'The SIMAI Framework theme palette did not report the hue and chroma it used.',
            );
        }

        return ['css' => $css, 'palette' => ['hue' => $read['hue'], 'chroma' => $read['chroma']], 'count' => count($variables)];
    }

    /**
     * The rule SF.ThemePalette.themeToCss makes of the variables under :root.
     *
     * @param  array<string, string>  $variables
     */
    public static function rule(array $variables): string
    {
        $lines = [];
        foreach ($variables as $name => $value) {
            $lines[] = '  ' . $name . ': ' . $value . ';';
        }

        return ":root {\n" . implode("\n", $lines) . "\n}\n";
    }

    /**
     * The Framework's shared modules the palette imports through
     * SF.runtimeModule, by id: from ui-994f9db407fa, `deprecations`
     * (core/js/deprecations.js of the release). Docara bundles those bytes in
     * the Composition Recipe runtime projection of the same release, and they
     * are read only when its manifest's SHA-256 matches.
     *
     * @return array<string, string>
     */
    private static function sharedModules(): array
    {
        $root = FileRecipeCompiler::bundledFrameworkRoot();
        $manifest = $root === null ? null : json_decode((string) @file_get_contents($root . '/runtime-manifest.json'), true);
        $modules = [];
        foreach (['deprecations' => 'distr/core/js/deprecations.js'] as $id => $relativePath) {
            $expected = is_array($manifest) ? ($manifest['files'][$relativePath]['sha256'] ?? null) : null;
            $path = $root . '/' . $relativePath;
            $bytes = is_string($expected) && is_file($path) && ! is_link($path) ? @file_get_contents($path) : false;
            if (! is_string($bytes) || ! hash_equals($expected, hash('sha256', $bytes))) {
                throw new PortableConfigurationException(
                    'THEME_PALETTE_RUNTIME_UNAVAILABLE',
                    "The SIMAI Framework module [$relativePath] the theme palette needs is missing from the bundled release.",
                );
            }
            $modules[$id] = $bytes;
        }

        return $modules;
    }

    /**
     * @param  array<string, string>  $modules
     * @param  array{color: string|null, hue: int|null, chroma: int|null}  $input
     * @return array<string, mixed>
     */
    private function run(string $source, array $modules, array $input): array
    {
        if (! is_file($this->adapter) || is_link($this->adapter)) {
            throw new PortableConfigurationException('THEME_PALETTE_RUNTIME_UNAVAILABLE', 'The theme palette adapter is missing.');
        }
        $process = new Process([$this->nodeBinary, $this->adapter]);
        $process->setTimeout(60);
        $process->setInput(json_encode(
            ['source' => $source, 'filename' => self::RUNTIME_PATH, 'modules' => $modules, ...$input],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
        $process->run();
        if (! $process->isSuccessful()) {
            throw new PortableConfigurationException(
                'THEME_PALETTE_FAILED',
                'The SIMAI Framework theme palette failed: ' . trim($process->getErrorOutput() ?: $process->getOutput()),
            );
        }
        try {
            $result = json_decode(trim($process->getOutput()), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'THEME_PALETTE_RESULT_INVALID',
                'The SIMAI Framework theme palette did not return JSON.',
                $exception,
            );
        }
        if (! is_array($result)) {
            throw new PortableConfigurationException('THEME_PALETTE_RESULT_INVALID', 'The SIMAI Framework theme palette did not return an object.');
        }

        return $result;
    }

    /**
     * The palette's variables, checked: first the 64 tones
     * `--sf-<palette>-<tone>` in the Framework's order, each #rrggbb; then,
     * from release ui-1cb0739a8423, the translucent steps
     * `--sf-<palette>-<tone>--alfa-<n>`, each exactly its tone's colour with
     * n% opacity as #rrggbbaa (alpha byte round(n * 255 / 100)).
     *
     * @return array<string, string>
     */
    public static function checkedVariables(mixed $variables): array
    {
        $expected = [];
        foreach (self::PALETTES as $palette) {
            foreach (self::TONES as $tone) {
                $expected[] = '--sf-' . $palette . '-' . $tone;
            }
        }
        $invalid = static fn (string $why): PortableConfigurationException => new PortableConfigurationException(
            'THEME_PALETTE_RESULT_INVALID',
            'The SIMAI Framework theme palette returned ' . $why . '.',
        );
        $tones = count($expected);
        if (! is_array($variables) || array_slice(array_keys($variables), 0, $tones) !== $expected) {
            throw $invalid('no ' . $tones . ' --sf-<palette>-<tone> primitives');
        }
        foreach (array_slice($variables, 0, $tones, true) as $value) {
            if (! is_string($value) || preg_match('/\A#[0-9a-f]{6}\z/D', $value) !== 1) {
                throw $invalid('a tone that is not a #rrggbb colour');
            }
        }
        $palettes = implode('|', self::PALETTES);
        $toneNames = implode('|', self::TONES);
        foreach (array_slice($variables, $tones, null, true) as $name => $value) {
            if (! is_string($name)
                || preg_match('/\A--sf-(' . $palettes . ')-(' . $toneNames . ')--alfa-([1-9][0-9]?)\z/D', $name, $match) !== 1
                || ! is_string($value)
            ) {
                throw $invalid('a variable that is neither a tone nor a translucent step');
            }
            $alpha = str_pad(dechex((int) round(((int) $match[3]) * 255 / 100)), 2, '0', STR_PAD_LEFT);
            if ($value !== $variables['--sf-' . $match[1] . '-' . $match[2]] . $alpha) {
                throw $invalid('a translucent step that is not its tone at its opacity');
            }
        }

        return $variables;
    }
}
