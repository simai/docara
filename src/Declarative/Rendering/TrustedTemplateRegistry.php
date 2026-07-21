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
        'demonstrator.docara.index' => ['path' => 'demonstrator/templates/index.php', 'renderer' => 'php'],
        'demonstrator.docara.detail' => ['path' => 'demonstrator/templates/detail.php', 'renderer' => 'php'],
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

        $root = realpath($this->resourceRoot);
        $path = $this->resourceRoot . '/' . $record['path'];
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
