<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\TelegramBot\Api\Command;
use StephanSchuler\TelegramBot\Channel\Events\EventConsumerClosure;
use StephanSchuler\TelegramBot\Channel\Events\EventEmitter;
use function preg_quote;
use function str_replace;

class EventLoop
{
    private $eventConsumer;
    private $eventEmitter;
    private $commands = [];

    public function __construct(EventEmitter $eventEmitter)
    {
        $this->eventConsumer = EventConsumerClosure::create(function ($data) {
            $this->dispatchData($data);
        });

        $this->eventEmitter = $eventEmitter;
        $this->eventEmitter->register($this->eventConsumer);
    }

    public function dispatchData($data)
    {
        $text = $data['message']['text'] ?? '';
        foreach ($this->commands as $command) {
            assert($command instanceof Delegation);
            if ($command->matches($text)) {
                $command->__invoke($data);
            }
        }
    }

    public function registerCommand(Command $command)
    {
        $pattern = str_replace(
            '[:COMMAND:]',
            preg_quote($command->getCommand(), '%'),
            '%^/[:COMMAND:](@[^\s]+)?(\s|$)%'
        );
        $callable = [$command, 'run'];
        $this->commands[] = new Delegation(
            $pattern,
            $callable
        );
    }
}