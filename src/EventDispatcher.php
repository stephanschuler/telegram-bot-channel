<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

class EventDispatcher
{
    private $consumers = [];


    public function then(callable $consumer): void
    {
        $this->consumers[] = $consumer;
    }

    public function dispatch($data)
    {
        foreach ($this->consumers as $consumer) {
            $consumer($data);
        }
    }
}