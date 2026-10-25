<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

/**
 * One use of a former parameter name or value. The page still renders as
 * before; the record only tells the author what to write instead.
 */
final readonly class ParameterDeprecation
{
    public const CODE = 'MARKDOWN_PARAMETER_DEPRECATED';

    public function __construct(
        /** The name the page uses to call the component: details, badge, image, quote. */
        public string $component,
        /** "name" for a renamed parameter, "value" for a renamed value. */
        public string $kind,
        /** The current parameter name. */
        public string $parameter,
        public string $deprecated,
        public string $replacement,
    ) {}

    public function message(): string
    {
        return $this->kind === 'name'
            ? "Parameter [{$this->deprecated}] of [{$this->component}] is deprecated; write [{$this->replacement}] instead. The page renders the same."
            : "Value [{$this->deprecated}] of parameter [{$this->parameter}] in [{$this->component}] is deprecated; write [{$this->replacement}] instead. The page renders the same.";
    }

    /** @return array{component:string,kind:string,parameter:string,deprecated:string,replacement:string} */
    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'kind' => $this->kind,
            'parameter' => $this->parameter,
            'deprecated' => $this->deprecated,
            'replacement' => $this->replacement,
        ];
    }
}
