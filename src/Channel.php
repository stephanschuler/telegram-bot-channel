<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use Psr\Http\Message\ResponseInterface;
use StephanSchuler\Events\ClosureBasedListener;
use StephanSchuler\Events\EventEmitter;
use StephanSchuler\Events\Events;
use StephanSchuler\TelegramBot\Api\Connection;
use StephanSchuler\TelegramBot\Api\Sendable\GetUpdates;
use StephanSchuler\TelegramBot\Api\Types\Chat;
use function json_decode;

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
        $this->events = EventEmitter::create();
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

    public function getEvents(): Events
    {
        return $this->scheduled(function () {
            return $this->events->getEvents();
        });
    }

    public function tap(callable $consumer): self
    {
        $this->getEvents()
            ->register(
                ClosureBasedListener::create($consumer)
            );
        return $this;
    }

    public function getEventLoop(): EventLoop
    {
        return $this->scheduled(function () {
            $eventLoop = new EventLoop(
                $this->events->getEvents()
            );
            return $eventLoop;
        });
    }

    public function trackConversation(Chat $chat, Chat $user): Conversation
    {
        return Conversation::create($this, $chat, $user);
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

    private function schedule(): void
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
                        $this->events->dispatch(new Update($message));
                        $this->lastSeenMessageId = $message['update_id'];
                    }
                    $this->schedule();
                }
            });
    }
}