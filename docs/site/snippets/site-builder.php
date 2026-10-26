<?php

declare(strict_types=1);

final class SiteBuilder
{
    // #region build
    public function build(string $source): string
    {
        $pages = $this->read($source);

        return $this->write($pages);
    }
    // #endregion build

    // #region read
    private function read(string $source): array
    {
        return glob($source . '/*.md') ?: [];
    }
    // #endregion read

    private function write(array $pages): string
    {
        return count($pages) . ' page(s) built';
    }
}
