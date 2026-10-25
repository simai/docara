<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Simai\Docara\ComponentCatalog\ComponentParameterModel;
use Simai\Docara\ComponentCatalog\ParameterDeprecation;
use Simai\Docara\Portable\PortableConfigurationException;

final class AuthoringAttributeParser
{
    public function __construct(private readonly ?ComponentParameterModel $parameters = null) {}

    /**
     * Parses a component's attribute block. A former parameter name or value
     * the catalogue still accepts arrives under its current name and value,
     * so a renderer only ever sees the current vocabulary.
     *
     * @return array<string, string>
     */
    public function parse(string $source, string $component): array
    {
        return $this->parseWithDeprecations($source, $component)[0];
    }

    /** @return array{0: array<string, string>, 1: list<ParameterDeprecation>} */
    public function parseWithDeprecations(string $source, string $component): array
    {
        return ($this->parameters ?? ComponentParameterModel::bundled())
            ->normalize($component, $this->parseLiteral($source, $component));
    }

    /** @return array<string, string> the attributes exactly as written */
    public function parseLiteral(string $source, string $component): array
    {
        $source = trim($source);
        if ($source === '') {
            return [];
        }

        $attributes = [];
        $offset = 0;
        $length = strlen($source);
        while ($offset < $length) {
            if (preg_match('/\G\s*([a-z][a-z0-9_-]*)\s*=\s*/A', $source, $match, 0, $offset) !== 1) {
                throw $this->invalid($component, $source);
            }
            $name = $match[1];
            $offset += strlen($match[0]);
            if (isset($attributes[$name])) {
                throw new PortableConfigurationException(
                    'MARKDOWN_COMPONENT_ATTRIBUTE_DUPLICATE',
                    "Markdown component [$component] declares attribute [$name] more than once.",
                );
            }

            if ($offset >= $length) {
                throw $this->invalid($component, $source);
            }
            $quote = $source[$offset];
            if ($quote === '"' || $quote === "'") {
                $offset++;
                $value = '';
                $closed = false;
                while ($offset < $length) {
                    $character = $source[$offset++];
                    if ($character === '\\' && $offset < $length) {
                        $value .= $source[$offset++];

                        continue;
                    }
                    if ($character === $quote) {
                        $closed = true;
                        break;
                    }
                    $value .= $character;
                }
                if (! $closed) {
                    throw $this->invalid($component, $source);
                }
            } elseif (preg_match('/\G([^\s{}]+)/A', $source, $valueMatch, 0, $offset) === 1) {
                $value = $valueMatch[1];
                $offset += strlen($valueMatch[0]);
            } else {
                throw $this->invalid($component, $source);
            }

            if ($value === '') {
                throw $this->invalid($component, $source);
            }
            $attributes[$name] = $value;
        }

        ksort($attributes, SORT_STRING);

        return $attributes;
    }

    private function invalid(string $component, string $source): PortableConfigurationException
    {
        return new PortableConfigurationException(
            'MARKDOWN_COMPONENT_ATTRIBUTES_INVALID',
            "Markdown component [$component] has invalid attributes [$source].",
        );
    }
}
