<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Provider;

use Cake\Core\Configure;
use Crustum\BroadcastingNotification\Channel\BroadcastChannel;
use Crustum\Notification\Extension\ChannelProviderInterface;
use Crustum\Notification\Registry\ChannelRegistry;

/**
 * Broadcast Channel Provider
 *
 * Registers the Broadcast channel with the notification system.
 */
class BroadcastChannelProvider implements ChannelProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provides(): array
    {
        return ['broadcast'];
    }

    /**
     * @inheritDoc
     */
    public function register(ChannelRegistry $registry): void
    {
        $config = array_merge(
            $this->getDefaultConfig(),
            (array)Configure::read('Notification.channels.broadcast', []),
        );

        $registry->load('broadcast', [
            'className' => BroadcastChannel::class,
        ] + $config);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConfig(): array
    {
        return [];
    }
}
