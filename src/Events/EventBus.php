<?php

namespace Simai\Docara\Events;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Simai\Docara\Docara;

/**
 * @method void beforeBuild(\callable|class-string|array<int, class-string> $listener)
 * @method void afterCollections(\callable|class-string|array<int, class-string> $listener)
 * @method void afterBuild(\callable|class-string|array<int, class-string> $listener)
 */
class EventBus
{
    /** @var Collection */
    public Collection $beforeBuild;

    /** @var Collection */
    public Collection $afterCollections;

    /** @var Collection */
    public Collection $afterBuild;

    public function __construct()
    {
        $this->beforeBuild = collect();
        $this->afterCollections = collect();
        $this->afterBuild = collect();
    }

    public function __call($event, $arguments)
    {
        if (isset($this->{$event})) {
            $this->{$event} = $this->{$event}->merge(Arr::wrap($arguments[0]));
        }
    }

    public function fire($event, Docara $docara): void
    {
        $this->{$event}->each(function ($task) use ($docara) {
            if (is_callable($task)) {
                $task($docara);
            } else {
                (new $task)->handle($docara);
            }
        });
    }
}
