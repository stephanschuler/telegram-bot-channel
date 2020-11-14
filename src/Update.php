<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\Events\Event;

/**
 * FIXME: This should be part of telegram-bot-api
 */
class Update implements Event
{
    private $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function toArray()
    {
        return $this->message;
    }

}