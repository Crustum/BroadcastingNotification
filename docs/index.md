# Broadcasting Notifications

- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
- [UI Widget](#ui-widget)
- [Prerequisites](#prerequisites)
- [Creating Broadcastable Notifications](#creating-broadcastable-notifications)
- [Formatting Broadcast Notifications](#formatting-broadcast-notifications)
    - [Using the Trait](#using-the-trait)
    - [Using BroadcastMessage](#using-broadcastmessage)
- [Broadcast Channels](#broadcast-channels)
    - [Private Channels](#private-channels)
    - [Public Channels](#public-channels)
    - [Presence Channels](#presence-channels)
- [Customizing Broadcast Behavior](#customizing-broadcast-behavior)
    - [Custom Event Names](#custom-event-names)
    - [Queue Configuration](#queue-configuration)
- [Listening for Broadcast Notifications](#listening-for-broadcast-notifications)
    - [Using Laravel Echo](#using-laravel-echo)
    - [Notification Format](#notification-format)
- [Combining with Other Channels](#combining-with-other-channels)
- [Testing](#testing)

<a name="introduction"></a>
## Introduction

The Broadcasting Notification plugin extends the CakePHP Notification system to support real-time notification delivery via WebSocket broadcasting. This allows you to send notifications that can be instantly displayed in your JavaScript-powered frontend without requiring page refreshes.

Broadcasting notifications work seamlessly alongside other notification channels like database, email, SMS, and Slack. You can send a notification through multiple channels simultaneously, with broadcasting providing the real-time user experience.

<a name="installation"></a>
## Installation

### Requirements

- PHP 8.1+
- CakePHP 5.0+
- CakePHP Notification Plugin
- CakePHP Broadcasting Plugin

### Installation via Composer

```bash
composer require crustum/notification-broadcasting
```

### Load the Plugin

The plugin is loaded via `config/plugins.php`:
```php
'Crustum/BroadcastingNotification' => [],
```

<a name="configuration"></a>
## Configuration

### Configure Broadcasting

First, configure the Broadcasting plugin in `config/app_local.php`:
```php
return [
    'Broadcasting' => [
        'default' => [
            'className' => 'Crustum/Broadcasting.Pusher',
            'app_id' => env('PUSHER_APP_ID'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ],
        ],
    ],
];
```

**Or use environment variables (.env):**
```
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=us2
```

<a name="ui-widget"></a>
## UI Widget (Real-time Notification Bell)

The Broadcasting plugin provides a bell element that wraps the base Notification plugin bell and adds real-time WebSocket capabilities via a JavaScript module.

### Hybrid Mode (Database + Broadcasting) - Recommended

```php
<?php $authUser = $this->request->getAttribute('identity'); ?>
<li class="nav-item">
    <?= $this->element('Crustum/NotificationUI.notifications/bell_icon', [
        'mode' => 'panel',
        'enablePolling' => true,
        'pollInterval' => 30000,
        'broadcasting' => [
            'userId' => $authUser->getIdentifier(),
            'userName' => $authUser->username ?? 'User',
            'pusherKey' => 'app-key',
            'pusherHost' => '127.0.0.1',
            'pusherPort' => 8080,
            'pusherCluster' => 'mt1',
        ],
    ]) ?>
</li>
```

### Broadcasting Only (No Database)

```php
<?= $this->element('Crustum/NotificationUI.notifications/bell_icon', [
    'mode' => 'panel',
    'enablePolling' => false,
    'broadcasting' => [
        'userId' => $authUser->getIdentifier(),
        'userName' => $authUser->username ?? 'User',
        'pusherKey' => env('PUSHER_APP_KEY'),
        'pusherHost' => env('PUSHER_HOST', '127.0.0.1'),
        'pusherPort' => env('PUSHER_PORT', 8080),
        'pusherCluster' => env('PUSHER_CLUSTER', 'mt1'),
    ],
]) ?>
```


### Configuration Options

```php
'broadcasting' => [
    'userId' => $authUser->getIdentifier(),
    'userName' => $authUser->username ?? 'User',
    'pusherKey' => env('PUSHER_APP_KEY'),
    'pusherHost' => env('PUSHER_HOST', '127.0.0.1'),
    'pusherPort' => env('PUSHER_PORT', 8080),
    'pusherCluster' => env('PUSHER_CLUSTER', 'mt1'),
    'pusherForceTLS' => env('PUSHER_FORCE_TLS', false),
    'debug' => env('APP_DEBUG', false),
]
```

<a name="prerequisites"></a>
## Prerequisites

Before broadcasting notifications, you should be familiar with CakePHP's Broadcasting services (provided by the BlazeCast plugin) and have it properly configured. Broadcasting notifications build on top of the event broadcasting infrastructure.

You should have:
1. BlazeCast plugin installed and configured
2. A WebSocket server running (typically via `bin/cake server blazecast`)
3. Frontend configured to listen for broadcasts (using Laravel Echo or similar)

<a name="creating-broadcastable-notifications"></a>
## Creating Broadcastable Notifications

To make a notification broadcastable, you can either use the `BroadcastableNotificationTrait` or return the `broadcast` channel from your notification's `via()` method. Using the trait is the recommended approach as it provides convenient methods for customizing broadcast behavior.

### Using Bake

You can generate a notification with broadcasting support:

```shell
bin/cake bake notification OrderShipped --channels database,broadcast
```

This will create a notification class with the necessary methods already scaffolded.

<a name="formatting-broadcast-notifications"></a>
## Formatting Broadcast Notifications

<a name="using-the-trait"></a>
### Using the Trait

The simplest way to make a notification broadcastable is to use the `BroadcastableNotificationTrait`:

```php
<?php
namespace App\Notification;

use Crustum\Broadcasting\Channel\Channel;
use Crustum\Broadcasting\Channel\PrivateChannel;
use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\BroadcastingNotification\Trait\BroadcastableNotificationTrait;
use Crustum\Notification\Notification;

class OrderShipped extends Notification
{
    use BroadcastableNotificationTrait;

    protected int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Get the notification delivery channels
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification
     */
    public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'message' => 'Your order has been shipped!',
        ];
    }

    /**
     * Get the channels to broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $notifiable->id),
        ];
    }
}
```

<a name="using-broadcastmessage"></a>
### Using BroadcastMessage

For more control over the broadcast, you can return a `BroadcastMessage` instance from your `toBroadcast()` method:

```php
use Crustum\BroadcastingNotification\Message\BroadcastMessage;

/**
 * Get the broadcast representation of the notification
 */
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): BroadcastMessage
{
    return (new BroadcastMessage([
        'order_id' => $this->orderId,
        'title' => 'Order Shipped',
        'message' => 'Your order has been shipped!',
        'tracking_number' => $this->trackingNumber,
        'action_url' => '/orders/view/' . $this->orderId,
        'icon_class' => 'fa fa-shipping-fast',  // Font Awesome icon
        'icon' => 'check',                      // Built-in SVG icon (fallback)
    ]))
        ->onQueue('broadcasts')
        ->onConnection('redis');
}
```

The `BroadcastMessage` class provides a fluent interface for configuring broadcast behavior:

```php
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): BroadcastMessage
{
    return (new BroadcastMessage($data))
        ->onQueue('priority-broadcasts')    // Specify queue name
        ->onConnection('redis')             // Specify queue connection
        ->delay(5);                         // Delay broadcast by 5 seconds
}
```

<a name="broadcast-channels"></a>
## Broadcast Channels

<a name="private-channels"></a>
### Private Channels

Private channels are the most common type of channel for broadcasting notifications. They ensure that only authorized users can listen to broadcasts on the channel.

```php
use Crustum\Broadcasting\Channel\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('users.' . $this->userId),
    ];
}
```

By default, notifications broadcast to a private channel using the pattern `{EntityType}.{id}`. For example, if you send a notification to a User entity with ID 5, it will broadcast on the `App.Model.Entity.User.5` channel.

You can customize the channel by overriding the `broadcastOn()` method as shown above.

<a name="public-channels"></a>
### Public Channels

Public channels allow anyone to listen to broadcasts without authentication:

```php
use Crustum\Broadcasting\Channel\Channel;

public function broadcastOn(): array
{
    return [
        new Channel('public.announcements'),
    ];
}
```

Use public channels sparingly and never broadcast sensitive information on them.

<a name="presence-channels"></a>
### Presence Channels

Presence channels are special private channels that track who is subscribed:

```php
use Crustum\Broadcasting\Channel\PresenceChannel;

public function broadcastOn(): array
{
    return [
        new PresenceChannel('team.' . $this->teamId),
    ];
}
```

<a name="customizing-broadcast-behavior"></a>
## Customizing Broadcast Behavior

<a name="custom-event-names"></a>
### Custom Event Names

By default, broadcasts use the notification's fully qualified class name as the event name. You can customize this by overriding the `broadcastAs()` method:

```php
/**
 * Get the broadcast event name
 */
public function broadcastAs(): ?string
{
    return 'order.shipped';
}
```

<a name="queue-configuration"></a>
### Queue Configuration

Broadcasting notifications can be queued for better performance. You can specify which queue and connection to use:

```php
/**
 * Get the queue name for broadcasts
 */
public function broadcastQueue(): ?string
{
    return 'high-priority';
}

/**
 * Get the queue connection for broadcasts
 */
public function broadcastConnection(): ?string
{
    return 'default';
}
```

Alternatively, configure these when creating the `BroadcastMessage`:

```php
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): BroadcastMessage
{
    return (new BroadcastMessage($data))
        ->onQueue('high-priority')
        ->onConnection('pusher');
}
```

<a name="listening-for-broadcast-notifications"></a>
## Listening for Broadcast Notifications

<a name="using-laravel-echo"></a>
### Using Laravel Echo

The easiest way to listen for broadcast notifications on the frontend is using Laravel Echo, which works seamlessly with CakePHP's broadcasting system:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
});

// Listen for notifications on a private channel
Echo.private(`users.${userId}`)
    .notification((notification) => {
        console.log(notification.type);
        console.log(notification.data);

        // Display notification to user
        displayNotification(notification);
    });
```

<a name="notification-format"></a>
### Notification Format

Broadcast notifications have a consistent format:

```javascript
{
    "type": "App\\Notification\\OrderShipped",
    "id": "unique-notification-id",
    "data": {
        "title": "Order Shipped",
        "message": "Your order has been shipped!",
        "order_id": 123,
        "tracking_number": "1Z999AA10123456784",
        "action_url": "/orders/view/123",
        "icon": "check",                    // Built-in SVG icon
        "icon_class": "fa fa-shipping-fast" // CSS class icon (takes precedence)
    }
}
```

**Icon Support:**

The notification system supports two types of icons:

1. **Built-in SVG Icons** (via `icon` field):
   - Available icons: `bell`, `post`, `user`, `message`, `alert`, `check`, `info`
   - No external dependencies, works out of the box
   - Example: `'icon' => 'post'`

2. **CSS Class Icons** (via `icon_class` field):
   - Font Awesome: `'icon_class' => 'fa fa-bell'`
   - Bootstrap Icons: `'icon_class' => 'bi bi-bell'`
   - Tabler Icons: `'icon_class' => 'ti ti-bell'`
   - Any CSS icon library you prefer
   - Requires loading the icon library CSS in your layout
   - Example: `'icon_class' => 'fa fa-shipping-fast'`

**Note:** If both `icon` and `icon_class` are provided, `icon_class` takes precedence.

You can access the notification data in your JavaScript:

```javascript
Echo.private(`users.${userId}`)
    .notification((notification) => {
        if (notification.type === 'App\\Notification\\OrderShipped') {
            showToast(`Order #${notification.data.order_id} has shipped!`);
            updateOrderStatus(notification.data.order_id, 'shipped');
        }
    });
```

### Filtering Notification Types

You can listen for specific notification types:

```javascript
// Listen for all notifications
Echo.private(`users.${userId}`)
    .notification((notification) => {
        handleNotification(notification);
    });

// Or filter in your handler
Echo.private(`users.${userId}`)
    .notification((notification) => {
        switch (notification.type) {
            case 'App\\Notification\\OrderShipped':
                handleOrderShipped(notification);
                break;
            case 'App\\Notification\\PaymentReceived':
                handlePaymentReceived(notification);
                break;
        }
    });
```

<a name="combining-with-other-channels"></a>
## Combining with Other Channels

Broadcasting works seamlessly with other notification channels. You can send a notification via multiple channels simultaneously:

```php
<?php
namespace App\Notification;

use Crustum\Broadcasting\Channel\PrivateChannel;
use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\BroadcastingNotification\Trait\BroadcastableNotificationTrait;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Message\MailMessage;
use Crustum\Notification\Notification;
use Crustum\Notification\ShouldQueueInterface;

class OrderShipped extends Notification implements ShouldQueueInterface
{
    use BroadcastableNotificationTrait;

    protected int $orderId;
    protected string $trackingNumber;

    public function __construct(int $orderId, string $trackingNumber)
    {
        $this->orderId = $orderId;
        $this->trackingNumber = $trackingNumber;
    }

    /**
     * Get the notification delivery channels
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Get the database representation
     */
    public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): DatabaseMessage
    {
        return (new DatabaseMessage())->data([
            'order_id' => $this->orderId,
            'message' => 'Your order has been shipped',
            'tracking_number' => $this->trackingNumber,
        ]);
    }

    /**
     * Get the broadcast representation
     */
    public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'message' => 'Your order has been shipped!',
            'tracking_number' => $this->trackingNumber,
        ];
    }

    /**
     * Get the mail representation
     */
    public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
    {
        return MailMessage::create()
            ->subject('Your Order Has Shipped')
            ->greeting('Good news!')
            ->line('Your order has been shipped and is on its way.')
            ->line("Tracking Number: {$this->trackingNumber}")
            ->action('Track Your Order', url('/orders/' . $this->orderId));
    }

    /**
     * Get the channels to broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $notifiable->id),
        ];
    }
}
```

This notification will:
1. Store a record in the database for persistence
2. Send a real-time broadcast to the user's private channel
3. Send an email with tracking information

All three actions happen automatically when you call:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->notify($user, new OrderShipped($orderId, $trackingNumber));
```

<a name="testing"></a>
## Testing

When testing broadcast notifications, you can use the `NotificationTrait` to capture notifications and verify that broadcasts were sent. After adding the trait to your test case, you can assert that broadcast notifications were sent and inspect their data structure and channels:

```php
<?php
namespace App\Test\TestCase;

use App\Notification\OrderShipped;
use Crustum\Notification\TestSuite\NotificationTrait;
use Cake\TestSuite\TestCase;

class BroadcastNotificationTest extends TestCase
{
    use NotificationTrait;

    protected array $fixtures = ['app.Users', 'app.Orders'];

    public function testBroadcastNotificationIsSent(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new OrderShipped(123, 'TRACK123'));

        $this->assertNotificationSentTo($user, OrderShipped::class);
        $this->assertNotificationSentToChannel('broadcast', OrderShipped::class);
    }

    public function testBroadcastDataStructure(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new OrderShipped(123, 'TRACK123'));

        $notifications = $this->getNotificationsByClass(OrderShipped::class);
        $notification = $notifications[0]['notification'];

        $broadcastData = $notification->toBroadcast($user);

        $this->assertArrayHasKey('order_id', $broadcastData);
        $this->assertArrayHasKey('message', $broadcastData);
        $this->assertArrayHasKey('tracking_number', $broadcastData);
        $this->assertEquals(123, $broadcastData['order_id']);
        $this->assertEquals('TRACK123', $broadcastData['tracking_number']);
    }

    public function testBroadcastChannels(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new OrderShipped(123, 'TRACK123'));

        $notifications = $this->getNotificationsByClass(OrderShipped::class);
        $notification = $notifications[0]['notification'];

        $channels = $notification->broadcastOn();

        $this->assertNotEmpty($channels);
        $this->assertInstanceOf(
            'Crustum\Broadcasting\Channel\PrivateChannel',
            $channels[0]
        );
    }

    public function testNotificationIncludesBroadcastChannel(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new OrderShipped(123, 'TRACK123'));

        $notifications = $this->getNotificationsByClass(OrderShipped::class);
        $this->assertContains('broadcast', $notifications[0]['channels']);
    }
}
```

## Advanced Usage

### Broadcasting to Multiple Channels

You can broadcast a notification to multiple channels simultaneously:

```php
public function broadcastOn(): array
{
    return [
        new PrivateChannel('users.' . $this->userId),
        new Channel('orders.public'),
        new PresenceChannel('warehouse'),
    ];
}
```

### Conditional Broadcasting

You can conditionally determine broadcast channels based on the notifiable:

```php
public function broadcastOn(): array
{
    $channels = [
        new PrivateChannel('users.' . $this->userId),
    ];

    if ($this->order->is_gift) {
        $channels[] = new PrivateChannel('users.' . $this->order->recipient_id);
    }

    if ($this->user->isAdmin()) {
        $channels[] = new PrivateChannel('admin.notifications');
    }

    return $channels;
}
```

### Broadcasting with Additional Data

You can include additional metadata in your broadcasts:

```php
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): array
{
    return [
        'order_id' => $this->orderId,
        'message' => 'Your order has been shipped!',
        'timestamp' => time(),
        'priority' => 'high',
        'actions' => [
            ['text' => 'Track Order', 'url' => '/orders/' . $this->orderId],
            ['text' => 'Contact Support', 'url' => '/support'],
        ],
    ];
}
```

### Real-time UI Updates

Broadcast notifications are perfect for real-time UI updates:

```javascript
// Update UI when notification arrives
Echo.private(`users.${userId}`)
    .notification((notification) => {
        // Update badge count
        updateNotificationCount();

        // Add to notification dropdown
        addNotificationToDropdown(notification);

        // Show toast
        showToast(notification.data.message);

        // Update relevant page sections
        if (notification.type === 'App\\Notification\\OrderShipped') {
            updateOrderStatus(notification.data.order_id);
        }
    });
```

### Notification Sound and Desktop Notifications

You can trigger browser notifications and sounds:

```javascript
Echo.private(`users.${userId}`)
    .notification((notification) => {
        // Play notification sound
        playNotificationSound();

        // Show browser notification (requires permission)
        if (Notification.permission === 'granted') {
            new Notification(notification.data.message, {
                body: 'Click to view details',
                icon: '/img/notification-icon.png',
            });
        }

        // Update UI
        displayNotification(notification);
    });
```

## Best Practices

1. **Keep Broadcast Data Small**: Only include essential data in broadcasts to minimize bandwidth.

2. **Use Private Channels**: Always use private channels for user-specific notifications to ensure security.

3. **Queue Long-Running Operations**: Implement `ShouldQueueInterface` to queue notification sending and prevent blocking requests.

4. **Handle Connection Failures**: Implement reconnection logic in your frontend to handle WebSocket disconnections.

5. **Combine with Database Channel**: Always send broadcast notifications alongside database storage for persistence.

6. **Test Thoroughly**: Test both the broadcast and database storage to ensure users can see notifications even if they miss the real-time broadcast.

7. **Monitor Performance**: Keep an eye on your WebSocket server performance, especially under high load.

8. **Use Meaningful Event Names**: Override `broadcastAs()` to use clear, semantic event names.

## Troubleshooting

### Notifications Not Broadcasting

1. Check that BlazeCast is properly configured and running
2. Verify WebSocket server is accessible
3. Ensure notification includes 'broadcast' in `via()` return array
4. Check that `broadcastOn()` returns valid channels

### Frontend Not Receiving Broadcasts

1. Verify Echo is properly configured
2. Check channel name matches exactly
3. Ensure user is authorized for private channels
4. Check browser console for connection errors

### Performance Issues

1. Queue broadcast notifications using `ShouldQueueInterface`
2. Use Redis for queue backend
3. Run multiple queue workers
4. Optimize broadcast payload size
5. Consider using fan-out pattern for broadcasts to many users

