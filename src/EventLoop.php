<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\TelegramBot\Api\Command;
use StephanSchuler\TelegramBot\Channel\Events\ClosureBasedListener;
use StephanSchuler\TelegramBot\Channel\Events\Events;
use function preg_quote;
use function str_replace;

class EventLoop
{
    private $eventConsumer;
    private $events;
    private $commands = [];

    public function __construct(Events $events)
    {
        $this->eventConsumer = ClosureBasedListener::create(function ($data) {
            $this->dispatchData($data);
        });

        $this->events = $events;
        $this->events->register($this->eventConsumer);
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