<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class Translator
{
    public function __construct(
        private LocaleRegistry $locales,
        private LanguagePackRepository $packs,
    ) {}

    /** @param array<string, scalar> $parameters */
    public function message(string $locale, string $id, array $parameters = []): string
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:\.[a-z][a-z0-9_-]*)+$/D', $id) !== 1) {
            throw new PortableConfigurationException('MESSAGE_ID_INVALID', "Message ID [$id] is invalid.");
        }
        foreach ($this->locales->fallbackChain($locale) as $candidate) {
            $pack = $this->packs->load($candidate);
            if (! array_key_exists($id, $pack->messages)) {
                continue;
            }
            $message = $pack->messages[$id];
            foreach ($parameters as $name => $value) {
                if (preg_match('/^[a-z][a-z0-9_]*$/D', $name) !== 1) {
                    throw new PortableConfigurationException(
                        'MESSAGE_PARAMETER_INVALID',
                        "Message parameter [$name] is invalid.",
                    );
                }
                $message = str_replace('{' . $name . '}', (string) $value, $message);
            }

            return $message;
        }

        throw new PortableConfigurationException(
            'MESSAGE_NOT_FOUND',
            "Message [$id] is not available for locale [" . LocaleTag::from($locale)->value() . '] or its fallbacks.',
        );
    }

    /** @return array<string, mixed> */
    public function component(string $locale, string $componentId): array
    {
        $resolved = [];
        $found = false;
        $chain = array_reverse($this->locales->fallbackChain($locale));
        foreach ($chain as $candidate) {
            $presentation = $this->packs->load($candidate)->components[$componentId] ?? null;
            if (! is_array($presentation)) {
                continue;
            }
            $resolved = $this->merge($resolved, $presentation);
            $found = true;
        }
        if (! $found) {
            throw new PortableConfigurationException(
                'COMPONENT_PRESENTATION_NOT_FOUND',
                "Component [$componentId] has no presentation for locale [" . LocaleTag::from($locale)->value() . '] or its fallbacks.',
            );
        }

        return $resolved;
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $overlay @return array<string, mixed> */
    private function merge(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            if (is_array($value) && ! array_is_list($value) && is_array($base[$key] ?? null)
                && ! array_is_list($base[$key])
            ) {
                $base[$key] = $this->merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
