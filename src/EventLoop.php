<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\Events\Event;
use StephanSchuler\TelegramBot\Api\Command;
use StephanSchuler\Events\ClosureBasedListener;
use StephanSchuler\Events\Events;
use function preg_quote;
use function str_replace;

class EventLoop
{
    private $eventConsumer;
    private $events;
    private $commands = [];

    public function __construct(Events $events)
    {
        $this->eventConsumer = ClosureBasedListener::create(function (Event $data) {
            $this->dispatchData($data);
        });

        $this->events = $events;
        $this->events->register($this->eventConsumer);
    }

    public function dispatchData(Event $event)
    {
        if (!($event instanceof Update)) {
            return;
        }
        $message = $event->toArray();
        $text = $message['message']['text'] ?? '';
        foreach ($this->commands as $command) {
            assert($command instanceof Delegation);
            if ($command->matches($text)) {
                $command->__invoke($message);
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