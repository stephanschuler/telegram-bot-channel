<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

use function assert;
use function spl_object_id;

class EventEmitter
{
    private const DROP = false;
    private const KEEP = true;

    protected $bindings = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function dispatch($event): void
    {
        $listeners = [];

        foreach ($this->bindings as $binding) {
            assert($binding instanceof Binding);
            $consumer = $binding->getListener();
            if ($consumer instanceof Listener
                && !isset($listeners[spl_object_id($consumer)])
                && $binding->matchesCondition($event)
            ) {
                $listeners[spl_object_id($consumer)] = $consumer;
            }
        }

        foreach ($listeners as $consumer) {
            assert($consumer instanceof Listener);
            $consumer->__invoke($event);
        }
    }

    public function getEvents(): Events
    {
        return Events::create($this);
    }

    public function register(Listener $listener, callable $condition): callable
    {
        $binding = Binding::create($listener)
            ->withCondition($condition);
        $this->bindings[] = $binding;
        return function () use ($listener, $condition) {
            $this->unregister($listener, $condition);
        };
    }

    public function unregister(Listener $listener, ?callable $condition = null): void
    {
        $this->bindings = array_filter(
            $this->bindings,
            static function (Binding $binding) use ($listener, $condition) {
                $delinquent = $binding->getListener();
                $delinquentCondition = $binding->getCondition();
                $bindingIsExpired = !($delinquent instanceof Listener);
                switch (true) {
                    case ($bindingIsExpired):
                    case ($listener === $delinquent && $condition === $delinquentCondition):
                    case ($listener === $delinquent && $condition === null):
                        return self::DROP;
                }
                return self::KEEP;
            }
        );
    }
}