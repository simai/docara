<?php

declare(strict_types=1);

namespace Simai\Docara\Authoring;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class AuthoringContract
{
    /** @param list<string> $audiences @param list<array{match:string,profile:string}> $rules */
    private function __construct(
        public bool $present,
        public array $audiences = [],
        public ?string $defaultProfile = null,
        public array $rules = [],
        public ?string $sha256 = null,
    ) {}

    public static function load(string $root): self
    {
        $path = rtrim($root, '/\\') . '/docara.authoring.json';
        if (! file_exists($path) && ! is_link($path)) {
            return new self(false);
        }
        $stat = @stat($path);
        if (! is_file($path) || is_link($path) || ! is_array($stat) || (int) ($stat['nlink'] ?? 0) !== 1) {
            throw new PortableConfigurationException('AUTHORING_FILE_UNSAFE', 'docara.authoring.json must be a regular single-link file.');
        }
        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException('AUTHORING_JSON_INVALID', 'docara.authoring.json must be valid JSON.', $exception);
        }
        if (! is_array($data)) {
            throw new PortableConfigurationException('AUTHORING_SCHEMA_INVALID', 'docara.authoring.json must contain an object.');
        }
        (new SchemaRepository)->assertValid($data, 'authoring.schema.json');

        return new self(true, $data['audiences'] ?? [], $data['default_profile'] ?? null, $data['rules'] ?? [], hash_file('sha256', $path) ?: null);
    }

    /** @return array{profile:?string,source:string,matches:list<array{match:string,profile:string}>} */
    public function resolve(string $relativePath, ?string $override): array
    {
        if ($override !== null) {
            $this->assertProfile($override);

            return ['profile' => $override, 'source' => 'front_matter', 'matches' => []];
        }
        $matches = array_values(array_filter($this->rules, fn (array $rule): bool => $this->matches($rule['match'], $relativePath)));
        $profiles = array_values(array_unique(array_column($matches, 'profile')));
        if (count($profiles) > 1) {
            throw new PortableConfigurationException('AUTHORING_RULE_CONFLICT', "Rules assign conflicting profiles to [$relativePath].");
        }
        if ($profiles !== []) {
            return ['profile' => $profiles[0], 'source' => 'path_rule', 'matches' => $matches];
        }

        return ['profile' => $this->defaultProfile, 'source' => $this->defaultProfile === null ? 'none' : 'default', 'matches' => []];
    }

    private function assertProfile(string $profile): void
    {
        if (! in_array($profile, AuthoringProfileRegistry::IDS, true)) {
            throw new PortableConfigurationException('AUTHORING_PROFILE_UNKNOWN', "Unknown page profile [$profile].");
        }
    }

    private function matches(string $pattern, string $path): bool
    {
        $quoted = preg_quote($pattern, '~');
        $quoted = str_replace(['\\*\\*', '\\*', '\\?'], ['.*', '[^/]*', '[^/]'], $quoted);

        return preg_match('~^' . $quoted . '$~D', $path) === 1;
    }
}
