<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\SiteTheme;
use Symfony\Component\Process\Process;

/**
 * A site theme computed by the pinned SIMAI Framework's theme palette.
 *
 * The Framework makes a theme from one colour (SF.ThemePalette,
 * core/js/theme-palette.js, on Google's HCT): eight tonal palettes of sixteen
 * tones, the 128 `--sf-<palette>-<tone>` primitives every semantic token reads
 * through light-dark(). Docara does not reimplement it. It runs the release's
 * own bytes -- the sf-theme-generator entrypoint of the bundled Smart
 * projection, checked against the lock's SHA-256 -- with the Node.js binary
 * that already runs the Composition Recipe runtime, and takes `themeToCss`'s
 * rule as the site's stylesheet.
 *
 * The result is checked before it is used: exactly the eight palettes and
 * sixteen tones, each a #rrggbb value, and the CSS byte-equal to the rule
 * those variables make. So the stylesheet can hold nothing but the theme.
 */
final class FrameworkThemePalette
{
    public const RUNTIME_PATH = 'smart/theme-generator/js/theme-generator.js';

    public const PALETTES = ['primary', 'secondary', 'tertiary', 'neutral', 'error', 'warning', 'success', 'info'];

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
        $result = $this->run($source, $theme);
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
        $content = '/* docara-theme: ' . $theme->color . ' (hue ' . $read['hue'] . ', chroma ' . $read['chroma']
            . '), SIMAI Framework ' . $pair . ' SF.ThemePalette.themeToCss */' . "\n" . $css;
        $sha256 = hash('sha256', $content);

        return [
            'content' => $content,
            'receipt' => [
                'schema' => SiteTheme::SCHEMA,
                'input' => $theme->input(),
                'palette' => [
                    'hue' => $read['hue'],
                    'chroma' => $read['chroma'],
                ],
                'framework' => [
                    'pair_id' => $pair,
                    'runtime' => self::RUNTIME_PATH,
                    'sha256' => hash('sha256', $source),
                ],
                'stylesheet' => [
                    'filename' => 'docara-theme.' . $sha256 . '.css',
                    'sha256' => $sha256,
                    'variables' => count($variables),
                ],
            ],
        ];
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

    /** @return array<string, mixed> */
    private function run(string $source, SiteTheme $theme): array
    {
        if (! is_file($this->adapter) || is_link($this->adapter)) {
            throw new PortableConfigurationException('THEME_PALETTE_RUNTIME_UNAVAILABLE', 'The theme palette adapter is missing.');
        }
        $process = new Process([$this->nodeBinary, $this->adapter]);
        $process->setTimeout(60);
        $process->setInput(json_encode(
            ['source' => $source, 'filename' => self::RUNTIME_PATH, ...$theme->input()],
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
     * The palette's variables, checked: first the 128 tones
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
        if (! is_array($variables) || array_slice(array_keys($variables), 0, 128) !== $expected) {
            throw $invalid('no 128 --sf-<palette>-<tone> primitives');
        }
        foreach (array_slice($variables, 0, 128, true) as $value) {
            if (! is_string($value) || preg_match('/\A#[0-9a-f]{6}\z/D', $value) !== 1) {
                throw $invalid('a tone that is not a #rrggbb colour');
            }
        }
        $palettes = implode('|', self::PALETTES);
        $tones = implode('|', self::TONES);
        foreach (array_slice($variables, 128, null, true) as $name => $value) {
            if (! is_string($name)
                || preg_match('/\A--sf-(' . $palettes . ')-(' . $tones . ')--alfa-([1-9][0-9]?)\z/D', $name, $match) !== 1
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
