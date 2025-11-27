# CakePHP BroadcastingNotification Plugin

The **BroadcastingNotification** plugin extends the CakePHP Notification system to support real-time notification delivery via WebSocket broadcasting. This allows you to send notifications that can be instantly displayed in your JavaScript-powered frontend without requiring page refreshes.

Broadcasting notifications work seamlessly alongside other notification channels like database, email, SMS, and Slack. You can send a notification through multiple channels simultaneously, with broadcasting providing the real-time user experience while other channels ensure reliable delivery.

The plugin supports private channels for user-specific notifications, public channels for open communication, presence channels for tracking active users, and queue support for background broadcasting. It integrates with the CakePHP Broadcasting plugin and includes a UI widget for displaying real-time notifications.

## Requirements

* PHP 8.2+
* CakePHP Notification Plugin
* CakePHP Broadcasting Plugin

See [Versions.md](docs/Versions.md) for the supported CakePHP versions.

## Documentation

For documentation, as well as tutorials, see the [docs](docs/index.md) directory of this repository.

## License

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.

