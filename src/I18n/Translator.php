<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class Translator
{
    /**
     * With $fallbacksNeedOwnLanguage a locale reaches its declared fallbacks
     * only when it keeps its own lang.json: an incomplete file borrows the
     * missing required strings, while a locale with no file at all still
     * fails instead of silently showing another language. A string with a
     * built-in default (messageOr) never comes from a fallback locale: the
     * locale's own text, then the built-in default.
     */
    public function __construct(
        private LocaleRegistry $locales,
        private ?ContentLanguageRepository $contentLanguages = null,
        private bool $allowMessageFallbacks = true,
        private bool $fallbacksNeedOwnLanguage = false,
    ) {}

    /** @param array<string, scalar> $parameters */
    public function message(string $locale, string $id, array $parameters = []): string
    {
        return $this->lookup($locale, $id, $parameters, true);
    }

    /** @param array<string, scalar> $parameters */
    private function lookup(string $locale, string $id, array $parameters, bool $useFallbacks): string
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:\.[a-z][a-z0-9_-]*)+$/D', $id) !== 1) {
            throw new PortableConfigurationException('MESSAGE_ID_INVALID', "Message ID [$id] is invalid.");
        }
        $definition = $this->locales->get($locale);
        $fallbacks = $useFallbacks && $this->allowMessageFallbacks
            && (! $this->fallbacksNeedOwnLanguage || $this->contentLanguages?->hasOwnLanguage($definition) === true);
        $candidates = $fallbacks ? $this->locales->fallbackChain($locale) : [$definition];
        foreach ($candidates as $candidate) {
            $contentMessages = $this->contentLanguages?->messages($candidate) ?? [];
            if (array_key_exists($id, $contentMessages)) {
                return $this->replace($contentMessages[$id], $parameters);
            }
        }

        throw new PortableConfigurationException(
            'MESSAGE_NOT_FOUND',
            "Message [$id] is not available for locale [" . LocaleTag::from($locale)->value() . ']'
                . ($fallbacks && count($candidates) > 1 ? ' or its fallbacks.' : '.'),
        );
    }

    /** @param array<string, scalar> $parameters */
    public function messageOr(string $locale, string $id, string $default, array $parameters = []): string
    {
        try {
            return $this->lookup($locale, $id, $parameters, ! $this->fallbacksNeedOwnLanguage);
        } catch (PortableConfigurationException $exception) {
            if ($exception->errorCode !== 'MESSAGE_NOT_FOUND') {
                throw $exception;
            }

            return $this->replace($default, $parameters);
        }
    }

    /** @param array<string, scalar> $parameters */
    private function replace(string $message, array $parameters): string
    {
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
}
