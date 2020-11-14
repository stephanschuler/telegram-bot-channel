<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\TelegramBot\Api\Connection;
use StephanSchuler\TelegramBot\Api\Sendable\SendMessage;
use StephanSchuler\TelegramBot\Api\Types\Chat;
use StephanSchuler\TelegramBot\Api\Types\Message;
use StephanSchuler\Events\Events;

final class Conversation
{
    private $channel;
    private $events;
    private $chat;

    private function __construct(Channel $channel, Chat $chat, Chat $user)
    {
        $filter = static function (array $data) use ($chat, $user) {
            $messageChat = Chat::forUser($data['message']['chat']['id'] ?? 0);
            $messageUser = Chat::forUser($data['message']['from']['id'] ?? 0);
            return $messageChat->equals($chat)
                && $messageUser->equals($user);
        };
        $this->events = $channel
            ->getEvents()
            ->filter($filter);
        $this->channel = $channel;
        $this->chat = $chat;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }

    public function getConnection(): Connection
    {
        return $this->channel->getConnection();
    }

    public function answer(Message $message): self
    {
        $this
            ->getConnection()
            ->send(
                SendMessage::create($this->chat, $message)
            );
        return $this;
    }

    public static function create(Channel $channel, Chat $chat, Chat $user): self
    {
        return new static($channel, $chat, $user);
    }
}