<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The background effects catalogue of a site (docara.json `effects`): where
 * SIMAI Framework SF.Effects loads the effects that a hero names
 * (`:::hero {effect="..."}`). The build writes it to
 * `SF_BOOT_CONFIG.effects` as {catalogue, index, indexSha256}.
 *
 * SF.Effects checks every effect file against the catalogue index before it
 * runs it, and runs only effects under the catalogue, so a hero effect needs
 * this setting, and an effect named by address must lie under `catalogue`.
 * Pin the catalogue to a commit, never to a branch.
 */
final readonly class EffectsCatalogue
{
    private function __construct(
        public string $catalogue,
        public ?string $index,
        public ?string $indexSha256,
    ) {}

    /** @param array<string, mixed> $site */
    public static function fromSite(array $site): ?self
    {
        $effects = $site['effects'] ?? null;
        if ($effects === null) {
            return null;
        }
        if (! is_array($effects) || array_is_list($effects)) {
            throw new PortableConfigurationException('EFFECTS_INVALID', 'effects must be an object.');
        }
        $catalogue = $effects['catalogue'] ?? null;
        if (! is_string($catalogue)
            || strlen($catalogue) > 2048
            || preg_match('#\Ahttps://[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?(?::[0-9]{1,5})?/(?:[A-Za-z0-9._~%@+-]+/)*\z#D', $catalogue) !== 1
        ) {
            throw new PortableConfigurationException(
                'EFFECTS_CATALOGUE_INVALID',
                'effects.catalogue must be an https address ending in /, pinned to a commit, such as https://cdn.jsdelivr.net/gh/simai/ui-effects@<commit>/.',
            );
        }
        $index = $effects['index'] ?? null;
        if ($index !== null && (! is_string($index) || preg_match('#\A[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)*\.json\z#D', $index) !== 1 || str_contains('/' . $index . '/', '/../'))) {
            throw new PortableConfigurationException('EFFECTS_INDEX_INVALID', 'effects.index must be a relative path to a .json file under the catalogue.');
        }
        $sha = $effects['index_sha256'] ?? null;
        if ($sha !== null && (! is_string($sha) || preg_match('/\A[0-9a-f]{64}\z/D', $sha) !== 1)) {
            throw new PortableConfigurationException('EFFECTS_INDEX_SHA256_INVALID', 'effects.index_sha256 must be 64 lowercase hex characters.');
        }

        return new self($catalogue, $index, $sha);
    }

    /**
     * Fails when a page names an effect the catalogue cannot serve.
     *
     * @param  self|null  $catalogue  the site's catalogue, or null
     */
    public static function assertPage(?self $catalogue, string $html, string $where): void
    {
        if (preg_match_all('/<section\b[^>]*\sdata-sf-effect="([^"]*)"/', $html, $matches) < 1) {
            return;
        }
        if ($catalogue === null) {
            throw new PortableConfigurationException(
                'EFFECTS_CATALOGUE_REQUIRED',
                "$where uses a background effect; set effects.catalogue in docara.json.",
            );
        }
        foreach ($matches[1] as $reference) {
            $reference = html_entity_decode($reference, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (str_starts_with($reference, 'https://') && ! str_starts_with($reference, $catalogue->catalogue)) {
                throw new PortableConfigurationException(
                    'EFFECTS_OUTSIDE_CATALOGUE',
                    "$where names the effect [$reference] outside effects.catalogue {$catalogue->catalogue}; SF.Effects runs only catalogue effects.",
                );
            }
        }
    }

    /** The `SF_BOOT_CONFIG.effects` value. */
    public function bootConfiguration(): array
    {
        return [
            'catalogue' => $this->catalogue,
            ...($this->index === null ? [] : ['index' => $this->index]),
            ...($this->indexSha256 === null ? [] : ['indexSha256' => $this->indexSha256]),
        ];
    }

    /** The boot script; it runs after the Framework boot configuration and before core.js. */
    public function bootScript(): string
    {
        return '<script data-docara-effects>window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.effects='
            . json_encode($this->bootConfiguration(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . ';</script>';
    }
}
