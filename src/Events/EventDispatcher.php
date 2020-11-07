<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class EventDispatcher /* implements \Psr\EventDispatcher\EventDispatcherInterface */
{
    private $emitter;

    private function __construct(EventEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    public static function create(): self
    {
        $emitter = new EventEmitter();
        return new self($emitter);
    }

    public function dispatch($event): void
    {
        $this->emitter->__emit($event);
    }

    public function getEmitter(): EventEmitter
    {
        return $this->emitter;
    }
}