<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

use Psr\Http\Message\ResponseInterface;
use StephanSchuler\TelegramBot\Api\Connection;
use StephanSchuler\TelegramBot\Api\Sendable\GetUpdates;

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
        $this->events = new EventDispatcher();
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
        if (!$this->scheduled) {
            $this->schedule();
        }

        $this->events->then($consumer);
        return $this;
    }

    public function getEventBus()
    {
        $eventBus = new EventLoop();
        $this->tap([$eventBus, 'dispatchData']);
        return $eventBus;
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