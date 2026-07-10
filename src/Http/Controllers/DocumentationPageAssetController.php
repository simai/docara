<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Http\Response;
use Larena\Core\Starter\CoreAssetActivationContract;
use Larena\Docara\Assets\DocumentationPageAssetManifest;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DocumentationPageAssetController
{
    public function __invoke(string $assetKey): Response
    {
        $asset = DocumentationPageAssetManifest::asset($assetKey);
        if ($asset === null) {
            throw new NotFoundHttpException('larena_docara_public_asset_unknown');
        }

        $path = $this->safeResourcePath((string) $asset['resource_path']);

        return new Response((string) file_get_contents($path), 200, [
            'Content-Type' => ($asset['kind'] ?? null) === 'js' ? 'application/javascript; charset=UTF-8' : 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Larena-Owner' => 'larena/docara',
            'X-Larena-Asset-Activation-Owner' => CoreAssetActivationContract::OWNER,
            'X-Larena-Root-Copy' => 'false',
        ]);
    }

    private function safeResourcePath(string $resourcePath): string
    {
        $reflection = new ReflectionClass(DocumentationPageAssetManifest::class);
        $packageRoot = dirname((string) $reflection->getFileName(), 3);
        $resourceRoot = realpath($packageRoot . '/resources');
        $path = realpath($packageRoot . '/' . ltrim($resourcePath, '/'));

        if ($resourceRoot === false || $path === false || !str_starts_with($path, $resourceRoot . DIRECTORY_SEPARATOR) || !is_file($path)) {
            throw new NotFoundHttpException('larena_docara_public_asset_path_invalid');
        }

        return $path;
    }
}
