<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Crustum\BroadcastingNotification\Provider\BroadcastChannelProvider;

/**
 * Broadcasting Notification Plugin
 *
 * Registers the Broadcast notification channel with the CakePHP Notification plugin.
 */
class BroadcastingNotificationPlugin extends BasePlugin
{
    /**
     * Bootstrap hook
     *
     * Registers the Broadcast channel with the notification registry.
     *
     * @param \Cake\Core\PluginApplicationInterface<\Cake\Core\PluginInterface> $app Application instance
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        EventManager::instance()->on(
            'Notification.Registry.discover',
            function ($event): void {
                $registry = $event->getSubject();
                (new BroadcastChannelProvider())->register($registry);
            },
        );
    }
}
