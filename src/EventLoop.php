<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\TelegramBot\Api\Command;
use function preg_quote;
use function str_replace;

class EventLoop
{
    private $commands = [];

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