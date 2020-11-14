<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use StephanSchuler\Events\Event;
use StephanSchuler\Events\Events;
use StephanSchuler\Events\Modification\Filter;
use StephanSchuler\TelegramBot\Api\Connection;
use StephanSchuler\TelegramBot\Api\Sendable\SendMessage;
use StephanSchuler\TelegramBot\Api\Types\Chat;
use StephanSchuler\TelegramBot\Api\Types\Message;

final class Conversation implements Filter
{
    private $channel;
    private $events;
    private $chat;
    private $user;

    private function __construct(Channel $channel, Chat $chat, Chat $user)
    {
        $this->events = $channel
            ->getEvents()
            ->filter($this);
        $this->channel = $channel;
        $this->chat = $chat;
        $this->user = $user;
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

    public function filterEvent(Event $event): bool
    {
        if (!$event instanceof Update) {
            return false;
        }
        $data = $event->toArray();
        $messageChat = Chat::forUser($data['message']['chat']['id'] ?? 0);
        $messageUser = Chat::forUser($data['message']['from']['id'] ?? 0);
        return $messageChat->equals($this->chat)
            && $messageUser->equals($this->user);
    }
}