<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class EventEmitter
{
    protected $consumers = [];

    public function filter(callable $filter): self
    {
        $dispatcher = EventDispatcher::create();
        $this->register(EventConsumerClosure::create(function ($event) use ($dispatcher): void {
            $dispatcher->dispatch($event);
        }));
        return $dispatcher->getEmitter();
    }

    public function register(EventConsumer $consumer): void
    {
        $this->consumers[] = $consumer;
    }

    public function unregister(EventConsumer $consumer): void
    {
        $this->consumers = array_filter($this->consumers, function (EventConsumer $delinquent) use ($consumer) {
            return $delinquent === $consumer;
        });
    }

    /** @internal */
    public function __emit($event): void
    {
        $consumers = $this->consumers;
        $this->consumers = [];
        array_walk($consumers, function (EventConsumer $consumer) use ($event) {
            $consumer->consume($event);
        });
        $this->consumers = array_merge($consumers, $this->consumers);
    }
}
