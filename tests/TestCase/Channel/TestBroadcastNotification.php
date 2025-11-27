<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Test\TestCase\Channel;

use Cake\Datasource\EntityInterface;
use Crustum\Broadcasting\Channel\PrivateChannel;
use Crustum\BroadcastingNotification\Trait\BroadcastableNotificationTrait;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test Broadcast Notification
 *
 * Simple notification for testing broadcast functionality
 */
class TestBroadcastNotification extends Notification
{
    use BroadcastableNotificationTrait;

    /**
     * Get channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['broadcast'];
    }

    /**
     * Get broadcast data
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string, mixed>
     */
    public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'message' => 'Test notification',
            'user_id' => $notifiable->get('id'),
        ];
    }

    /**
     * Get broadcast event name
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'notification.test.broadcast';
    }

    /**
     * Get broadcast channels
     *
     * @return array<\Crustum\Broadcasting\Channel\Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('test-channel')];
    }
}
