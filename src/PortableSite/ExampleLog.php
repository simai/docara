<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * An example's optional log, as `:::example {log="…" log_console=true
 * log_open=false}` asks for it: the event names Docara records on the
 * example's root, whether a Console tab records the example's console, and
 * whether the log opens collapsed.
 */
final readonly class ExampleLog
{
    /** At most this many event names, so one example cannot listen to everything. */
    public const MAX_EVENTS = 16;

    /** @param list<string> $events */
    public function __construct(
        public array $events,
        public bool $console,
        public bool $collapsed,
    ) {}

    /** An event name as a DOM event type is written: `click`, `sf-button-press`, `docara:ready`. */
    public static function isEventName(string $name): bool
    {
        return strlen($name) <= 64 && preg_match('/\A[A-Za-z][A-Za-z0-9]*(?:[._:-][A-Za-z0-9]+)*\z/', $name) === 1;
    }

    /** @return array{events: list<string>, console: bool} what a frame's log runtime needs */
    public function frameConfiguration(): array
    {
        return ['events' => $this->events, 'console' => $this->console];
    }
}
