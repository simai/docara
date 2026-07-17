import { spawn } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, normalize, resolve } from 'node:path';
import { defineConfig } from 'vite';

function copyIfExists(from, to) {
    if (! existsSync(from)) {
        return;
    }

    mkdirSync(dirname(to), { recursive: true });
    cpSync(from, to, { recursive: true });
}

function copyStaticAssets() {
    copyIfExists('source/_core/_assets/img', 'source/assets/build/img');
    copyIfExists('source/img', 'source/assets/build/img');
}

function findDocaraCommand() {
    const localBin = normalize('./vendor/bin/docara');
    if (existsSync(localBin)) {
        return {
            command: 'php',
            args: [localBin],
        };
    }

    return {
        command: 'docara',
        args: [],
    };
}

function runDocaraBuild(env) {
    if (process.env.DOCARA_SKIP_BUILD === '1') {
        return Promise.resolve();
    }

    return new Promise((resolveBuild, rejectBuild) => {
        const docaraCommand = findDocaraCommand();
        const child = spawn(docaraCommand.command, [...docaraCommand.args, 'build', env, '--cache', 'false'], {
            stdio: 'inherit',
            shell: process.platform === 'win32',
        });

        child.on('exit', (code) => {
            if (code > 0) {
                rejectBuild(new Error(`Docara build failed with exit code ${code}.`));
                return;
            }

            resolveBuild();
        });
    });
}

function docara(options = {}) {
    const modeToEnv = (mode) => mode === 'production' ? 'production' : 'local';
    const watchFiles = options.watch?.files ?? [
        'config.php',
        'bootstrap.php',
        'blade.php',
        'listeners/**/*.php',
        'source/**/*.md',
        'source/**/*.php',
        'source/**/*.html',
    ];

    let buildEnv = 'local';

    return {
        name: 'docara',
        configResolved(config) {
            buildEnv = modeToEnv(config.mode);
        },
        configureServer(server) {
            const writeHotFile = () => {
                const address = server.httpServer?.address();
                const protocol = server.config.server.https ? 'https' : 'http';
                const host = server.config.server.host === true || server.config.server.host === '0.0.0.0'
                    ? 'localhost'
                    : (server.config.server.host || 'localhost');
                const port = typeof address === 'object' && address ? address.port : server.config.server.port;

                writeFileSync('source/hot', `${protocol}://${host}:${port}`);
            };

            server.httpServer?.once('listening', () => {
                writeHotFile();
                copyStaticAssets();
                runDocaraBuild('local').catch((error) => {
                    server.config.logger.error(error.message);
                });
            });

            server.httpServer?.once('close', () => {
                rmSync('source/hot', { force: true });
            });

            server.watcher.add(watchFiles);
            server.watcher.on('change', (path) => {
                const normalizedPath = path.replaceAll('\\', '/');
                if (normalizedPath.includes('/source/assets/build/')) {
                    return;
                }

                runDocaraBuild('local').catch((error) => {
                    server.config.logger.error(error.message);
                });
            });

            return () => {
                server.middlewares.use((req, res, next) => {
                    const requestPath = req.url === '/' ? '/index.html' : req.url.split('?')[0];
                    const filePath = resolve('build_local', requestPath.replace(/^\/+/, ''));

                    if (! existsSync(filePath)) {
                        next();
                        return;
                    }

                    res.end(readFileSync(filePath));
                });
            };
        },
        async closeBundle() {
            copyStaticAssets();
            await runDocaraBuild(buildEnv);
        },
    };
}

export default defineConfig({
    publicDir: false,
    server: {
        host: '0.0.0.0',
        port: 3000,
    },
    build: {
        manifest: 'manifest.json',
        outDir: 'source/assets/build',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                main: 'source/_core/_assets/js/main.js',
                turbo: 'source/_core/_assets/js/turbo.js',
                styles: 'source/_core/_assets/css/main.scss',
            },
            output: {
                entryFileNames: 'js/[name].[hash].js',
                chunkFileNames: 'js/[name].[hash].js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';
                    const ext = name.split('.').pop();

                    if (ext === 'css') {
                        return 'css/[name].[hash][extname]';
                    }

                    if (['woff', 'woff2', 'ttf', 'otf', 'eot'].includes(ext)) {
                        return 'css/files/[name].[hash][extname]';
                    }

                    if (['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif'].includes(ext)) {
                        return 'img/[name].[hash][extname]';
                    }

                    return 'assets/[name].[hash][extname]';
                },
            },
        },
    },
    plugins: [
        docara(),
    ],
});
