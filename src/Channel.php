<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use Psr\Http\Message\ResponseInterface;
use StephanSchuler\TelegramBot\Api\Connection;
use StephanSchuler\TelegramBot\Api\Sendable\GetUpdates;
use StephanSchuler\TelegramBot\Api\Types\Chat;
use StephanSchuler\TelegramBot\Channel\Events\EventConsumerClosure;

final class Channel
{
    private $connection;
    private $lastSeenMessageId;
    private $timeoutInSeconds;
    private $events;
    private $scheduled = false;

    private function __construct(Connection $connection, int $lastSeenMessageId, int $timeoutInSeconds)
    {
        $this->connection = $connection;
        $this->lastSeenMessageId = $lastSeenMessageId;
        $this->timeoutInSeconds = $timeoutInSeconds;
        $this->events = Events\EventDispatcher::create();
        $this->events->name = 'channel';
    }

    public static function connectedTo(Connection $connection): self
    {
        return new static($connection, -1, 30);
    }

    public function withLastSeenMessageId(int $lastSeenMessageId): self
    {
        return new static($this->connection, $lastSeenMessageId, $this->timeoutInSeconds);
    }

    public function withFrameTimeout(int $timeoutInSeconds): self
    {
        return new static($this->connection, $this->lastSeenMessageId, $timeoutInSeconds);
    }

    public function tap(callable $consumer): self
    {
        return $this->scheduled(function () use ($consumer) {
            $this->events
                ->getEmitter()
                ->register(
                    EventConsumerClosure::create($consumer)
                );

            return $this;
        });
    }

    public function getEventBus(): EventLoop
    {
        return $this->scheduled(function () {
            $eventBus = new EventLoop(
                $this->events->getEmitter()
            );
            return $eventBus;
        });
    }

    public function trackConversation(Chat $chat, Chat $user)
    {
        $eventDispatcher = Events\EventDispatcher::create();
        $this->tap(static function ($data) use ($eventDispatcher, $chat, $user) {
            $messageChat = Chat::forUser($data['message']['chat']['id'] ?? 0);
            $messageUser = Chat::forUser($data['message']['from']['id'] ?? 0);
            if (!$messageChat->equals($chat)) {
                return;
            }
            if (!$messageUser->equals($user)) {
                return;
            }
            $eventDispatcher->dispatch($data);
        });
        return $eventDispatcher->getEmitter();
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    private function scheduled(callable $callable)
    {
        try {
            return $callable();
        } finally {
            if (!$this->scheduled) {
                $this->scheduled = true;
                $this->schedule();
            }
        }
    }

    private function schedule()
    {
        $this->connection
            ->send(
                GetUpdates::create()
                    ->withTimeout($this->timeoutInSeconds)
                    ->withOffset($this->lastSeenMessageId + 1)
            )
            ->then(function (ResponseInterface $response) {
                $result = json_decode($response->getBody()->getContents(), true);
                if ($result['ok'] === true) {
                    foreach ($result['result'] as $message) {
                        $this->events->dispatch($message);
                        $this->lastSeenMessageId = $message['update_id'];
                    }
                    $this->schedule();
                }
            });
    }
}