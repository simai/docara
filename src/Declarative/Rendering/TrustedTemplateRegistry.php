<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class TrustedTemplateRegistry
{
    /** @var array<string, array{path:string,renderer:string}> */
    private const APPLICATION_TEMPLATES = [
        'preview.docara.page' => ['path' => 'previews/templates/page.php', 'renderer' => 'php'],
        'preview.docara.index' => ['path' => 'previews/templates/index.php', 'renderer' => 'php'],
        'publisher.docara.page' => ['path' => 'publisher/templates/page.php', 'renderer' => 'php'],
        'publisher.docara.head' => ['path' => 'publisher/components/head.php', 'renderer' => 'php'],
        'publisher.docara.header-actions' => ['path' => 'publisher/components/header-actions.php', 'renderer' => 'php'],
        'publisher.docara.mobile-navigation' => ['path' => 'publisher/components/mobile-navigation.php', 'renderer' => 'php'],
        'publisher.docara.breadcrumbs' => ['path' => 'publisher/components/breadcrumbs.php', 'renderer' => 'php'],
        'publisher.docara.mobile-toc' => ['path' => 'publisher/components/mobile-toc.php', 'renderer' => 'php'],
        'publisher.docara.pager' => ['path' => 'publisher/components/pager.php', 'renderer' => 'php'],
        'publisher.docara.search-dialog' => ['path' => 'publisher/components/search-dialog.php', 'renderer' => 'php'],
        'publisher.docara.reader-settings' => ['path' => 'publisher/components/reader-settings.php', 'renderer' => 'php'],
    ];

    private SmartRegistry $smarts;

    public function __construct(
        private string $resourceRoot = __DIR__ . '/../../../resources',
        private RegisteredBladeRenderer $blade = new RegisteredBladeRenderer,
        ?SmartRegistry $smarts = null,
    ) {
        $this->smarts = $smarts ?? SmartRegistry::bundled();
    }

    /** @param array<string, object> $context */
    public function render(string $templateId, array $context): string
    {
        foreach ($context as $name => $value) {
            if (! is_string($name)
                || preg_match('/^[a-z][a-zA-Z0-9]*$/D', $name) !== 1
                || ! is_object($value)
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_TEMPLATE_CONTEXT_INVALID',
                    "Template [$templateId] received an invalid context.",
                );
            }
        }

        return $this->renderRegistered($templateId, $context);
    }

    /**
     * Render the exact source-pinned SF5 Smart template ABI v1.
     *
     * @param  array{id:string,smart:string,manifest:array<string,mixed>,view:array<string,mixed>,preset:array<string,mixed>,props:array<string,mixed>,childrenHtml:string,slot:string}  $context
     */
    public function renderPortable(string $templateId, array $context): string
    {
        $expected = ['childrenHtml', 'id', 'manifest', 'preset', 'props', 'slot', 'smart', 'view'];
        $actual = array_keys($context);
        sort($actual, SORT_STRING);
        if ($actual !== $expected
            || ! is_string($context['id'] ?? null)
            || ! is_string($context['smart'] ?? null)
            || ! is_array($context['manifest'] ?? null)
            || ! is_array($context['view'] ?? null)
            || ! is_array($context['preset'] ?? null)
            || ! is_array($context['props'] ?? null)
            || ! is_string($context['childrenHtml'] ?? null)
            || ! is_string($context['slot'] ?? null)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PORTABLE_TEMPLATE_CONTEXT_INVALID',
                "Portable template [$templateId] received an invalid SF5 v1 context.",
            );
        }

        return $this->renderRegistered($templateId, $context);
    }

    /** @param array<string, mixed> $context */
    private function renderRegistered(string $templateId, array $context): string
    {
        $record = self::APPLICATION_TEMPLATES[$templateId] ?? null;
        if (! is_array($record)) {
            try {
                $record = $this->smarts->template($templateId);
            } catch (\InvalidArgumentException) {
                $record = null;
            }
        }
        if (! is_array($record)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_TEMPLATE_NOT_ALLOWED',
                "Template [$templateId] is not registered.",
            );
        }
        $templateRoot = is_string($record['root'] ?? null) ? $record['root'] : $this->resourceRoot;
        $root = realpath($templateRoot);
        $path = $templateRoot . '/' . $record['path'];
        $real = realpath($path);
        $stat = @lstat($path);
        if ($root === false
            || $real === false
            || ! is_array($stat)
            || is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || ! str_starts_with($real, $root . DIRECTORY_SEPARATOR)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_TEMPLATE_UNSAFE',
                "Registered template [$templateId] is missing or unsafe.",
            );
        }

        $render = static function (string $trustedPath, array $trustedContext): string {
            extract($trustedContext, EXTR_SKIP);
            ob_start();
            try {
                require $trustedPath;

                return (string) ob_get_clean();
            } catch (\Throwable $exception) {
                ob_end_clean();
                throw $exception;
            }
        };
        $html = $record['renderer'] === 'blade'
            ? $this->blade->render($real, $context)
            : $render($real, $context);
        if ($html === '') {
            throw new PortableConfigurationException(
                'DECLARATIVE_TEMPLATE_EMPTY',
                "Template [$templateId] rendered an empty result.",
            );
        }

        return $html;
    }
}
