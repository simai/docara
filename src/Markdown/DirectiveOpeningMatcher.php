<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;

final readonly class DirectiveOpeningMatcher
{
    private const FRAMEWORK_NAME_PATTERN = '/\Aui(?:\.[a-z][a-z0-9_]*)+\z/D';

    /** @param list<string> $portableNames */
    public function __construct(private array $portableNames)
    {
        foreach ($portableNames as $name) {
            if (! is_string($name)
                || preg_match('/^[a-z][a-z0-9_]*$/D', $name) !== 1
                || str_starts_with($name, 'ui')
            ) {
                throw new \InvalidArgumentException(
                    'Portable directive names must be safe lower-case identifiers outside the reserved ui namespace.',
                );
            }
        }
    }

    public static function bundled(): self
    {
        return new self(TypedComponentDefinitionRepository::bundled()->names());
    }

    public static function isCanonicalFrameworkName(string $name): bool
    {
        return preg_match(self::FRAMEWORK_NAME_PATTERN, $name) === 1;
    }

    /** @return array{name: string, fence_length: int, family: string}|null */
    public function match(string $line): ?array
    {
        $names = $this->portableAlternation();
        // Keep the lexical catch intentionally broader than admission. Any
        // whitespace-free token that starts with "ui" must reach the strict
        // canonical-ID gate instead of becoming inert Markdown after a typo.
        $pattern = '/^(:{3,})(' . $names . '|ui[^\s]*)[ \t]*$/u';
        if (preg_match($pattern, $line, $match) !== 1) {
            return null;
        }

        return [
            'name' => $match[2],
            'fence_length' => strlen($match[1]),
            'family' => in_array($match[2], $this->portableNames, true)
                ? DirectiveBlockStartParser::PORTABLE
                : DirectiveBlockStartParser::FRAMEWORK,
        ];
    }

    public function matches(string $line, ?string $family = null): bool
    {
        $opening = $this->match($line);

        return $opening !== null && ($family === null || $opening['family'] === $family);
    }

    public function matchesPlacement(string $line, ?string $family = null): bool
    {
        if (preg_match('/^ {0,3}(.*)$/u', $line, $match) !== 1) {
            return false;
        }

        return $this->matches($match[1], $family);
    }

    public function belongsToFamily(string $name, string $family): bool
    {
        return $family === DirectiveBlockStartParser::PORTABLE
            ? in_array($name, $this->portableNames, true)
            : str_starts_with($name, 'ui');
    }

    private function portableAlternation(): string
    {
        $names = $this->portableNames;
        usort($names, static fn (string $left, string $right): int => strlen($right) <=> strlen($left) ?: $left <=> $right);

        return implode('|', array_map(
            static fn (string $name): string => preg_quote($name, '/'),
            $names,
        ));
    }
}
