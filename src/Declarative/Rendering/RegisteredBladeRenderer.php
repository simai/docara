<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Simai\Docara\Portable\PortableConfigurationException;

final class RegisteredBladeRenderer
{
    private readonly Filesystem $files;

    private readonly BladeCompiler $compiler;

    private readonly string $cachePath;

    public function __construct(?string $cachePath = null)
    {
        $this->files = new Filesystem;
        $this->cachePath = $cachePath ?? sys_get_temp_dir() . '/docara-declarative-blade';
        if (! is_dir($this->cachePath)
            && ! @mkdir($this->cachePath, 0700, true)
            && ! is_dir($this->cachePath)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLADE_CACHE_UNAVAILABLE',
                'The registered Blade renderer cannot create its private cache.',
            );
        }
        $this->compiler = new BladeCompiler($this->files, $this->cachePath);
    }

    /** @param array<string, object> $context */
    public function render(string $trustedPath, array $context): string
    {
        $source = (string) file_get_contents($trustedPath);
        $compiled = $this->compiler->compileString($source);
        $compiledPath = $this->cachePath . '/' . hash('sha256', $trustedPath . "\0" . $source) . '.php';
        if (! is_file($compiledPath)) {
            $temporary = tempnam($this->cachePath, 'compile-');
            if ($temporary === false
                || file_put_contents($temporary, $compiled, LOCK_EX) === false
                || ! @rename($temporary, $compiledPath)
            ) {
                if (is_string($temporary) && is_file($temporary)) {
                    @unlink($temporary);
                }
                throw new PortableConfigurationException(
                    'DECLARATIVE_BLADE_COMPILE_FAILED',
                    'The registered Blade template could not be compiled.',
                );
            }
            @chmod($compiledPath, 0600);
        }

        $render = static function (string $path, array $values): string {
            extract($values, EXTR_SKIP);
            ob_start();
            try {
                require $path;

                return (string) ob_get_clean();
            } catch (\Throwable $exception) {
                ob_end_clean();
                throw $exception;
            }
        };

        return $render($compiledPath, $context + ['__env' => new BladeEnvironment]);
    }
}
