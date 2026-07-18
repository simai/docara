<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final class ComponentDirectiveParser
{
    public function parse(string $markdown, string $pagePath): ParsedComponentDirectives
    {
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
        $fenceCharacter = null;
        $fenceLength = 0;
        $ordinal = 0;

        for ($index = 0, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];
            if ($fenceCharacter !== null) {
                $output[] = $line;
                if (preg_match('/^\s*' . preg_quote($fenceCharacter, '/') . '{' . $fenceLength . ',}\s*$/', $line) === 1) {
                    $fenceCharacter = null;
                    $fenceLength = 0;
                }

                continue;
            }

            if (preg_match('/^\s*(`{3,}|~{3,})/', $line, $fence) === 1) {
                $fenceCharacter = $fence[1][0];
                $fenceLength = strlen($fence[1]);
                $output[] = $line;

                continue;
            }

            if (preg_match('/^\s*:::((?:ui\.)[a-z][a-z0-9._-]*)\s*$/', $line, $opening) !== 1) {
                $output[] = $line;

                continue;
            }

            $component = $opening[1];
            if (! in_array($component, ['ui.alert', 'ui.button'], true)) {
                throw new FrameworkComponentException('FRAMEWORK_COMPONENT_UNSUPPORTED', $component . ':' . ($index + 1));
            }

            $payloadLines = [];
            $startLine = $index + 1;
            $closed = false;
            while (++$index < $count) {
                if (preg_match('/^\s*:::\s*$/', $lines[$index]) === 1) {
                    $closed = true;
                    break;
                }
                $payloadLines[] = $lines[$index];
            }
            if (! $closed) {
                throw new FrameworkComponentException('FRAMEWORK_DIRECTIVE_UNCLOSED', $component . ':' . $startLine);
            }

            $payload = trim(implode("\n", $payloadLines));
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
            $placeholder = 'DOCARA_COMPONENT_'
                . strtoupper(substr(hash('sha256', $pagePath . "\0" . $ordinal . "\0" . $component), 0, 24));
            $directives[] = new ComponentDirective($component, $props, $ordinal, $startLine, $placeholder);
            $output[] = $placeholder;
        }

        $result = implode($newline, $output);
        if ($hasTrailingNewline) {
            $result .= $newline;
        }

        return new ParsedComponentDirectives($result, $directives);
    }
}
