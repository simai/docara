<?php

declare(strict_types=1);

namespace Larena\Docara\Composition;

use Larena\Filesystem\Persistence\DatabaseLogicalFileRepository;
use Larena\Filesystem\Services\SafeFileService;
use Larena\Layout\Contracts\PageBlockInstance;
use Larena\Layout\Contracts\PageComposition;

final readonly class DocaraPageBlockPresenter
{
    public function __construct(private DatabaseLogicalFileRepository $files, private SafeFileService $fileService)
    {
    }

    /** @return list<array<string,mixed>> */
    public function present(PageComposition $composition): array
    {
        $blocks = [];
        foreach ($composition->blocks as $block) {
            if (!$block->enabled) {
                continue;
            }
            $presented = $block->toArray();
            $presented['image_url'] = $this->imageUrl($block);
            if ($block->type === 'image' && $presented['image_url'] === null) {
                continue;
            }
            $blocks[] = $presented;
        }
        return $blocks;
    }

    private function imageUrl(PageBlockInstance $block): ?string
    {
        $ref = match ($block->type) {
            'image' => $block->settings['file_ref'] ?? '',
            'hero' => $block->settings['image_file_ref'] ?? '',
            default => '',
        };
        if ($ref === '') {
            return null;
        }
        $file = $this->files->find($ref);
        if ($file === null || $file->getAttribute('visibility') !== 'public' || !str_starts_with((string) $file->getAttribute('mime_type'), 'image/')) {
            return null;
        }
        return $this->fileService->publicUrl($file);
    }
}
