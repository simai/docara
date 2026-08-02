<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class Translator
{
    public function __construct(
        private LocaleRegistry $locales,
        private ?ContentLanguageRepository $contentLanguages = null,
        private bool $allowMessageFallbacks = true,
    ) {}

    /** @param array<string, scalar> $parameters */
    public function message(string $locale, string $id, array $parameters = []): string
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:\.[a-z][a-z0-9_-]*)+$/D', $id) !== 1) {
            throw new PortableConfigurationException('MESSAGE_ID_INVALID', "Message ID [$id] is invalid.");
        }
        $candidates = $this->allowMessageFallbacks
            ? $this->locales->fallbackChain($locale)
            : [$this->locales->get($locale)];
        foreach ($candidates as $candidate) {
            $contentMessages = $this->contentLanguages?->messages($candidate) ?? [];
            if (array_key_exists($id, $contentMessages)) {
                return $this->replace($contentMessages[$id], $parameters);
            }
        }

        throw new PortableConfigurationException(
            'MESSAGE_NOT_FOUND',
            "Message [$id] is not available for locale [" . LocaleTag::from($locale)->value() . ']'
                . ($this->allowMessageFallbacks ? ' or its fallbacks.' : '.'),
        );
    }

    /** @param array<string, scalar> $parameters */
    public function messageOr(string $locale, string $id, string $default, array $parameters = []): string
    {
        try {
            return $this->message($locale, $id, $parameters);
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
