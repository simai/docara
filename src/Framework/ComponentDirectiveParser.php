<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Markdown\CommonMarkInspector;
use Simai\Docara\Markdown\DirectiveBlockStartParser;
use Simai\Docara\Markdown\DirectiveLimitExceeded;

final class ComponentDirectiveParser
{
    private CommonMarkInspector $inspector;

    public function __construct()
    {
        $this->inspector = new CommonMarkInspector;
    }

    public function parse(string $markdown, string $pagePath): ParsedComponentDirectives
    {
        if (preg_match('//u', $markdown) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_MARKDOWN_INVALID');
        }

        try {
            $portableInspection = $this->inspector->inspectDirectives(
                $markdown,
                DirectiveBlockStartParser::PORTABLE,
            );
            foreach ($portableInspection['directives'] as $portable) {
                if ($this->inspector->containsDirectiveLikeOpening(
                    $portable['body'],
                    DirectiveBlockStartParser::FRAMEWORK,
                )) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_DIRECTIVE_NESTING_UNSUPPORTED',
                        $portable['name'] . ':' . $portable['start_line'],
                    );
                }
            }
            $inspection = $this->inspector->inspectDirectives($markdown, DirectiveBlockStartParser::FRAMEWORK);
        } catch (DirectiveLimitExceeded $exception) {
            throw new FrameworkComponentException(
                'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED',
                $exception->getMessage(),
            );
        }
        $this->assertDirectivePlacement($markdown, $inspection);
        $newline = str_contains($markdown, "\r\n") ? "\r\n" : "\n";
        $hasTrailingNewline = str_ends_with($markdown, "\n");
        // Do not use \R without the UTF-8 modifier: byte 0x85 can occur inside
        // Cyrillic text and would be mistaken for a NEL line separator.
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_MARKDOWN_INVALID');
        }
        if ($hasTrailingNewline && end($lines) === '') {
            array_pop($lines);
        }

        $output = [];
        $directives = [];
        $placeholders = [];
        $ordinal = 0;
        $byStartLine = [];
        foreach ($inspection['directives'] as $directive) {
            $byStartLine[$directive['start_line']] = $directive;
        }

        for ($index = 0, $count = count($lines); $index < $count; $index++) {
            $lineNumber = $index + 1;
            $line = $lines[$index];
            if (! isset($byStartLine[$lineNumber])) {
                $output[] = $line;

                continue;
            }

            $parsed = $byStartLine[$lineNumber];
            $component = $parsed['name'];
            if (! in_array($component, ['ui.alert', 'ui.button'], true)) {
                throw new FrameworkComponentException('FRAMEWORK_COMPONENT_UNSUPPORTED', $component . ':' . $lineNumber);
            }
            $startLine = $parsed['start_line'];
            if ($parsed['closed'] !== true) {
                throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_UNCLOSED', $component . ':' . $startLine);
            }

            $payload = trim($parsed['body']);
            if ($this->containsNestedDirective($parsed['body'])) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_DIRECTIVE_NESTING_UNSUPPORTED',
                    $component . ':' . $startLine,
                );
            }
            if ($payload === '') {
                $props = [];
            } else {
                try {
                    $props = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_JSON_INVALID', $component . ':' . $startLine);
                }
                if (! is_array($props) || ! str_starts_with(ltrim($payload), '{')) {
                    throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_PROPS_INVALID', $component . ':' . $startLine);
                }
            }

            $ordinal++;
            $counter = 0;
            do {
                $placeholder = 'DOCARA_COMPONENT_'
                    . strtoupper(substr(hash(
                        'sha256',
                        $pagePath . "\0" . $ordinal . "\0" . $component . "\0" . $counter,
                    ), 0, 24));
                $counter++;
            } while (str_contains($markdown, $placeholder) || isset($placeholders[$placeholder]));
            $placeholders[$placeholder] = true;
            $directives[] = new ComponentDirective($component, $props, $ordinal, $startLine, $placeholder);
            $output[] = '';
            $output[] = $placeholder;
            $output[] = '';
            $index = $parsed['end_line'] - 1;
        }

        $result = implode($newline, $output);
        if ($hasTrailingNewline) {
            $result .= $newline;
        }

        return new ParsedComponentDirectives($result, $directives);
    }

    private function containsNestedDirective(string $body): bool
    {
        if ($this->inspector->containsDirectiveLikeOpening($body)) {
            return true;
        }

        try {
            foreach ([DirectiveBlockStartParser::PORTABLE, DirectiveBlockStartParser::FRAMEWORK] as $family) {
                if ($this->inspector->inspectDirectives($body, $family)['directives'] !== []) {
                    return true;
                }
            }
        } catch (DirectiveLimitExceeded $exception) {
            throw new FrameworkComponentException(
                'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED',
                $exception->getMessage(),
            );
        }

        return false;
    }

    /** @param array<string, mixed> $inspection */
    private function assertDirectivePlacement(string $markdown, array $inspection): void
    {
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_MARKDOWN_INVALID');
        }
        $recognized = array_fill_keys(array_column($inspection['directives'], 'start_line'), true);
        $ownedBodyLines = [];
        foreach ($inspection['directives'] as $directive) {
            for ($line = $directive['start_line'] + 1; $line < $directive['end_line']; $line++) {
                $ownedBodyLines[$line] = true;
            }
        }
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            if (preg_match('/^( {0,3}):{3,}ui\.[a-z][a-z0-9._-]*[ \t]*$/u', $line, $match) !== 1
                || isset($recognized[$lineNumber])
                || isset($ownedBodyLines[$lineNumber])
                || isset($inspection['code_lines'][$lineNumber])
            ) {
                continue;
            }
            if (isset($inspection['nested_lines'][$lineNumber])) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_DIRECTIVE_INDENTATION_UNSUPPORTED',
                    (string) $lineNumber,
                );
            }
        }
    }
}
