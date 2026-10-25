<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Renders a fenced code block as CommonMark does, and keeps the block's
 * options for the code presenter.
 *
 * A fence's info string is the language and, in braces, the options Docara
 * reads (```php {title="app.php" highlight="2-3" numbers}). CommonMark keeps
 * only the first word of the info string, as the language class; the
 * options would be lost, so this renderer passes them on in
 * data-docara-code-options. A fence without braces renders exactly as
 * before.
 */
final class PortableFencedCodeRenderer implements NodeRendererInterface
{
    public const OPTIONS_ATTRIBUTE = 'data-docara-code-options';

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        FencedCode::assertInstanceOf($node);

        $attributes = $node->data->getData('attributes');
        [$language, $options] = self::splitInfo((string) $node->getInfo());
        if ($language !== '') {
            $attributes->append('class', str_starts_with($language, 'language-') ? $language : 'language-' . $language);
        }
        $exported = $attributes->export();
        if ($options !== null) {
            $exported[self::OPTIONS_ATTRIBUTE] = $options;
        }

        return new HtmlElement('pre', [], new HtmlElement('code', $exported, Xml::escape($node->getLiteral())));
    }

    /**
     * The language word and the option text between the braces, or null
     * when the fence carries no braces. Text outside the braces after the
     * language is kept for the presenter to reject.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function splitInfo(string $info): array
    {
        $info = trim($info);
        $words = preg_split('/\s+/', $info, 2) ?: [''];
        $brace = strpos($info, '{');
        if ($brace === false) {
            return [(string) $words[0], null];
        }
        $before = substr($info, 0, $brace);
        if (preg_match('/\s/', trim($before)) === 1) {
            // `php junk {…}`: the stray word goes to the presenter, which
            // rejects it rather than dropping it silently.
            return [(string) $words[0], trim((string) ($words[1] ?? ''))];
        }

        return [trim($before), substr($info, $brace)];
    }
}
