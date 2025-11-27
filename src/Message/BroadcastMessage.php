<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Message;

use Crustum\Notification\Message\PayloadTrait;

/**
 * Broadcast Message
 *
 * Represents a notification message that will be broadcast via WebSocket.
 * This class serves as a data container for broadcast notification payloads.
 *
 * Usage with array:
 * ```
 * return (new BroadcastMessage([
 *     'title' => 'New Message',
 *     'message' => 'You have a new message',
 * ]))
 *     ->onQueue('broadcasts')
 *     ->onConnection('redis');
 * ```
 *
 * Usage with fluent API:
 * ```
 * return BroadcastMessage::new()
 *     ->title('New Message')
 *     ->message('You have a new message')
 *     ->actionUrl('/messages/1')
 *     ->icon('envelope')
 *     ->type('info')
 *     ->onQueue('broadcasts');
 * ```
 *
 * @phpstan-consistent-constructor
 */
class BroadcastMessage
{
    use PayloadTrait;

    /**
     * The data for the notification
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * The queue connection to use
     *
     * @var string|null
     */
    protected ?string $connection = null;

    /**
     * The queue name to use
     *
     * @var string|null
     */
    protected ?string $queue = null;

    /**
     * Create a new broadcast message instance
     *
     * @param array<string, mixed> $data Notification data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Create a new broadcast message instance
     *
     * @param array<string, mixed> $data Initial notification data
     * @return static
     */
    public static function new(array $data = []): static
    {
        return new static($data);
    }

    /**
     * Set the data for the notification
     *
     * @param array<string, mixed> $data Notification data
     * @return static
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the notification data
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the queue connection to use
     *
     * @param string $connection Queue connection name
     * @return static
     */
    public function onConnection(string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the queue connection
     *
     * @return string|null
     */
    public function getConnection(): ?string
    {
        return $this->connection;
    }

    /**
     * Set the queue name to use
     *
     * @param string $queue Queue name
     * @return static
     */
    public function onQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get the queue name
     *
     * @return string|null
     */
    public function getQueue(): ?string
    {
        return $this->queue;
    }

    /**
     * Convert message to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
