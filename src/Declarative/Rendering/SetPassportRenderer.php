<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * A component set's footer line (block shell.set-passport): the set's name
 * and release, the Framework it needs, its licence, its source and the
 * site's version history. The facts come from the set's passport and package
 * receipt (SetPassportCatalogue), so the one outside link it carries is the
 * set's own GitHub repository.
 */
final readonly class SetPassportRenderer
{
    /**
     * @param  array<string, mixed>  $passport
     */
    public function render(array $passport, ?string $historyHref): string
    {
        $name = $passport['name'] ?? null;
        if (! is_string($name) || trim($name) === '') {
            throw new PortableConfigurationException(
                'SET_PASSPORT_INVALID',
                'A component set footer line needs the set name.',
            );
        }
        $parts = ['<span class="docara-set-passport-name">' . $this->escape($name) . '</span>'];
        foreach (['framework', 'licence'] as $key) {
            if (is_string($passport[$key] ?? null) && trim($passport[$key]) !== '') {
                $parts[] = '<span class="docara-set-passport-' . $key . '">' . $this->escape($passport[$key]) . '</span>';
            }
        }
        $source = $passport['source'] ?? null;
        if (is_array($source)
            && is_string($source['href'] ?? null)
            && preg_match('~\Ahttps://github\.com/[a-z0-9-]+/[a-z0-9._-]+\z~D', $source['href']) === 1
            && is_string($source['text'] ?? null)
            && trim($source['text']) !== ''
        ) {
            $parts[] = '<a class="docara-set-passport-source" href="' . $this->escape($source['href']) . '" rel="noopener">'
                . $this->escape($source['text']) . '</a>';
        }
        if ($historyHref !== null && is_string($passport['history'] ?? null) && trim($passport['history']) !== '') {
            $parts[] = '<a class="docara-set-passport-history" href="' . $this->escape($historyHref) . '">'
                . $this->escape($passport['history']) . '</a>';
        }

        return '<p class="docara-set-passport text-small color-on-surface-variant m-0" data-docara-set-passport="'
            . $this->escape((string) ($passport['package'] ?? '')) . '">'
            . implode('<span class="docara-set-passport-separator" aria-hidden="true"> · </span>', $parts)
            . '</p>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
