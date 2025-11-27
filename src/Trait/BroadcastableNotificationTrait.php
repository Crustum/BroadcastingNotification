<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Trait;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;

/**
 * Broadcastable Notification Trait
 *
 * Add this trait to notification classes that should support broadcasting.
 * Provides default implementations of broadcast-related methods.
 *
 * Usage:
 * ```
 * class OrderNotification extends Notification
 * {
 *     use BroadcastableNotificationTrait;
 *
 *     public function toBroadcast($notifiable): array
 *     {
 *         return ['order_id' => $this->orderId];
 *     }
 * }
 * ```
 */
trait BroadcastableNotificationTrait
{
    /**
     * Get channels to broadcast on
     *
     * Override this method to specify broadcast channels for the notification.
     *
     * @return array<\Crustum\Broadcasting\Channel\Channel> Array of channel instances
     */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Get the broadcast event name
     *
     * Override this method to customize the broadcast event name.
     *
     * @return string|null Event name or null to use default
     */
    public function broadcastAs(): ?string
    {
        return null;
    }

    /**
     * Get the queue name for broadcast
     *
     * Override this method to specify a different queue for broadcasts.
     *
     * @return string|null Queue name or null for default
     */
    public function broadcastQueue(): ?string
    {
        return null;
    }

    /**
     * Get the broadcast representation of the notification
     *
     * Override this method to provide notification data for broadcasting.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return array<string, mixed> Broadcast message data
     */
    public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        if (method_exists($this, 'toArray')) {
            return $this->toArray($notifiable);
        }

        return [];
    }
}
