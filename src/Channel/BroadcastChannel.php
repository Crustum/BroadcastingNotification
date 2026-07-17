<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Channel;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Crustum\Broadcasting\Broadcasting;
use Crustum\Broadcasting\Channel\PrivateChannel;
use Crustum\BroadcastingNotification\Message\BroadcastMessage;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Channel\ChannelInterface;
use Crustum\Notification\Notification;

/**
 * Broadcast Channel
 *
 * Sends notifications via the Broadcasting plugin for real-time delivery.
 * Does NOT store in database - only broadcasts to WebSocket/Pusher channels.
 *
 * Integrates with Crustum/Broadcasting plugin to send real-time notifications.
 *
 * @uses \Cake\ORM\Locator\LocatorAwareTrait
 * @phpstan-consistent-constructor
 */
class BroadcastChannel implements ChannelInterface
{
    use LocatorAwareTrait;

    /**
     * Configuration for the channel
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Channel configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Send the given notification by broadcasting it
     *
     * Uses the Broadcasting plugin to send real-time notifications.
     * Does not store anything in the database.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return null Always returns null as broadcasting has no meaningful response
     */
    public function send(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): mixed
    {
        $data = $this->getData($notifiable, $notification);

        $channels = [];
        if (method_exists($notification, 'broadcastOn')) {
            $channels = $notification->broadcastOn();
        }

        if (empty($channels)) {
            $channels = [new PrivateChannel($this->getNotifiableChannel($notifiable))];
        }

        $pending = Broadcasting::to($channels)
            ->event($this->getEventName($notification))
            ->data(array_merge($data, [
                'id' => $notification->getId(),
                'type' => $notification::class,
            ]));

        $queueName = null;
        if (method_exists($notification, 'broadcastQueue')) {
            $queueName = $notification->broadcastQueue();
        }

        if ($queueName !== null) {
            $pending->queue($queueName);
        } else {
            $pending->send();
        }

        return null;
    }

    /**
     * Get the notification data for broadcasting
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return array<string, mixed> The notification data
     * @throws \RuntimeException When notification doesn't implement required methods
     */
    protected function getData(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): array
    {
        if (!method_exists($notification, 'toBroadcast')) {
            return [];
        }

        $result = $notification->toBroadcast($notifiable);

        if ($result instanceof BroadcastMessage) {
            return $result->toArray();
        }

        return $result;
    }

    /**
     * Get the event name for the broadcast
     *
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @return string The event name
     */
    protected function getEventName(Notification $notification): string
    {
        if (method_exists($notification, 'broadcastAs')) {
            $eventName = $notification->broadcastAs();
            if ($eventName !== null) {
                return $eventName;
            }
        }

        $className = $notification::class;

        return str_replace('\\', '.', $className);
    }

    /**
     * Get the default notification channel name for the notifiable entity (static version)
     *
     * Generates a private channel name using Laravel's pattern:
     * - First checks for receivesBroadcastNotificationsOn() method on entity
     * - Falls back to {ClassName}.{id} format (e.g., "App.Model.Entity.User.123")
     *
     * This static method can be used by other services (e.g., NotificationUI) to get
     * the channel name without instantiating BroadcastChannel.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return string The channel name
     */
    public static function getNotifiableChannelName(EntityInterface|AnonymousNotifiable $notifiable): string
    {
        return (new static())->resolveNotifiableChannelName($notifiable);
    }

    /**
     * Get the default notification channel name for the notifiable entity
     *
     * Generates a private channel name using Laravel's pattern:
     * - First checks for receivesBroadcastNotificationsOn() method on entity
     * - Falls back to {ClassName}.{id} format (e.g., "App.Model.Entity.User.123")
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return string The channel name
     */
    protected function getNotifiableChannel(EntityInterface|AnonymousNotifiable $notifiable): string
    {
        return $this->resolveNotifiableChannelName($notifiable);
    }

    /**
     * Resolve the private channel name for a notifiable entity
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return string The channel name
     */
    protected function resolveNotifiableChannelName(EntityInterface|AnonymousNotifiable $notifiable): string
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            $route = $notifiable->routeNotificationFor('broadcast');

            return $route ?? 'anonymous';
        }

        if (method_exists($notifiable, 'receivesBroadcastNotificationsOn')) {
            return $notifiable->receivesBroadcastNotificationsOn();
        }

        $className = str_replace('\\', '.', $notifiable::class);
        $table = $this->getTableLocator()->get($notifiable->getSource());
        $primaryKeyName = $table->getPrimaryKey();

        if (is_array($primaryKeyName)) {
            $primaryKeyName = $primaryKeyName[0];
        }

        $primaryKeyValue = $notifiable->get($primaryKeyName);

        return "{$className}.{$primaryKeyValue}";
    }
}
