<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ViteStarterContractTest extends TestCase
{
    #[Test]
    public function maintainer_starter_has_one_reproducible_yarn_contract(): void
    {
        $root = dirname(__DIR__, 2) . '/stubs/site';
        $package = json_decode(
            (string) file_get_contents($root . '/source/_core/package.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $gitignore = (string) file_get_contents($root . '/.gitignore');

        self::assertSame('yarn@1.22.22', $package['packageManager']);
        self::assertSame('^20.19.0 || >=22.12.0', $package['engines']['node']);
        self::assertFileExists($root . '/source/_core/yarn.lock');
        self::assertStringNotContainsString('/yarn.lock', $gitignore);
        self::assertStringNotContainsString('/package-lock.json', $gitignore);
        self::assertStringNotContainsString('webpack.mix', $gitignore);
        self::assertStringContainsString('/source/hot', $gitignore);
        self::assertArrayNotHasKey('postinstall', $package['scripts']);
        self::assertFileDoesNotExist($root . '/source/_core/copy-template-configs.js');
        self::assertStringContainsString('$page->brand', (string) file_get_contents($root . '/source/_core/_components/header/logo.blade.php'));
        self::assertStringContainsString('$page->themeBuilder ?? false', (string) file_get_contents($root . '/source/_core/_layouts/main.blade.php'));
        self::assertStringContainsString("'themeBuilder' => false", (string) file_get_contents($root . '/config.php'));
        self::assertFileDoesNotExist($root . '/source/_core/_assets/img/icon_and_text_logo.svg');
        $head = (string) file_get_contents($root . '/source/_core/_layouts/head.blade.php');
        self::assertStringContainsString('$brandAssetUrl', $head);
        self::assertStringContainsString("\$brand['socialImage']", $head);
        self::assertStringContainsString("\$brand['favicon']", $head);
        self::assertStringNotContainsString('content="/assets/build/img/logo.svg"', $head);
        self::assertStringNotContainsString('href="/favicon.ico"', $head);
        $footer = (string) file_get_contents($root . '/source/_core/_layouts/footer.blade.php');
        self::assertStringContainsString('$page->footerContent', $footer);
        self::assertStringNotContainsString('simai.ru', $footer);
    }

    #[Test]
    public function vite_fails_closed_when_docara_cannot_start_or_is_signalled(): void
    {
        $config = (string) file_get_contents(
            dirname(__DIR__, 2) . '/stubs/site/source/_core/vite.config.js',
        );

        self::assertStringContainsString("process.env.DOCARA_PHP_BINARY || 'php'", $config);
        self::assertStringContainsString("child.once('error'", $config);
        self::assertStringContainsString("child.once('close', (code, signal)", $config);
        self::assertStringContainsString('code !== 0 || signal !== null', $config);
        self::assertStringContainsString('staticAssetFiles().forEach((file) => this.addWatchFile(file))', $config);
        self::assertStringContainsString("rmSync('source/assets/build/img', { recursive: true, force: true })", $config);
        self::assertStringContainsString('watchChange(path)', $config);
        self::assertStringContainsString("decodeURIComponent((requestUrl || '/').split('?')[0])", $config);
        self::assertStringContainsString("requestPath.split('/').includes('..')", $config);
        self::assertStringContainsString('realpathSync(candidate)', $config);
        self::assertStringContainsString('statSync(realCandidate).isFile()', $config);
        self::assertStringContainsString("'.html': 'text/html; charset=utf-8'", $config);
        self::assertStringContainsString("'.js': 'text/javascript; charset=utf-8'", $config);
        self::assertStringContainsString("'.json': 'application/json; charset=utf-8'", $config);
        self::assertStringContainsString("'.svg': 'image/svg+xml'", $config);
        self::assertStringContainsString("'.woff2': 'font/woff2'", $config);
        self::assertStringContainsString("res.setHeader('Content-Type', buildFileContentType(filePath))", $config);
        self::assertStringContainsString("res.setHeader('X-Content-Type-Options', 'nosniff')", $config);
        self::assertStringContainsString("req.method === 'HEAD'", $config);
        self::assertStringContainsString("candidate = resolve(candidate, 'index.html')", $config);
        self::assertStringContainsString("if (config.command === 'build')", $config);
        self::assertStringContainsString("process.once('exit', cleanupHotFile)", $config);
        self::assertStringContainsString("process.prependOnceListener('SIGINT', handleSigint)", $config);
        self::assertStringContainsString("process.prependOnceListener('SIGTERM', handleSigterm)", $config);
        self::assertStringContainsString('process.exit(130)', $config);
        self::assertStringContainsString('process.exit(143)', $config);
        self::assertStringContainsString("process.removeListener('exit', cleanupHotFile)", $config);
        self::assertStringContainsString("candidate.startsWith('build_')", $config);
        self::assertStringContainsString('function isDocaraAuthorInput(path)', $config);
        self::assertStringContainsString('let buildQueue = Promise.resolve()', $config);
        self::assertStringContainsString("server.watcher.on('add', handleAuthorChange)", $config);
        self::assertStringContainsString("server.watcher.on('unlink', handleAuthorChange)", $config);
        self::assertStringNotContainsString('server.watcher.add(watchFiles)', $config);
        self::assertStringNotContainsString('if (code > 0)', $config);
    }
}
