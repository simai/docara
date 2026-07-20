<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class TrustedTemplateRegistry
{
    /** @var array<string, string> */
    private const TEMPLATES = [
        'layout.docara.docs' => 'layouts/templates/docara.docs.php',
        'section.docara.article' => 'sections/templates/docara.article.php',
        'section.docara.shell' => 'sections/templates/docara.shell.php',
        'smart.ui.alert.default' => 'smart/ui.alert/templates/default.php',
        'smart.docara.header.default' => 'smart/docara.header/templates/default.php',
        'smart.docara.navigation.default' => 'smart/docara.navigation/templates/default.php',
        'smart.docara.outline.default' => 'smart/docara.outline/templates/default.php',
        'preview.docara.page' => 'previews/templates/page.php',
        'preview.docara.index' => 'previews/templates/index.php',
    ];

    public function __construct(
        private string $resourceRoot = __DIR__ . '/../../../resources',
    ) {}

    /** @param array<string, object> $context */
    public function render(string $templateId, array $context): string
    {
        $relative = self::TEMPLATES[$templateId] ?? null;
        if (! is_string($relative)) {
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
        $path = $this->resourceRoot . '/' . $relative;
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
        $html = $render($real, $context);
        if ($html === '') {
            throw new PortableConfigurationException(
                'DECLARATIVE_TEMPLATE_EMPTY',
                "Template [$templateId] rendered an empty result.",
            );
        }

        return $html;
    }
}
