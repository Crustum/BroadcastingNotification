<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Test\TestCase\Provider;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Crustum\BroadcastingNotification\Channel\BroadcastChannel;
use Crustum\BroadcastingNotification\Provider\BroadcastChannelProvider;
use Crustum\Notification\Registry\ChannelRegistry;

/**
 * BroadcastChannelProvider Test Case
 */
class BroadcastChannelProviderTest extends TestCase
{
    /**
     * Test provides returns broadcast channel
     *
     * @return void
     */
    public function testProvidesReturnsBroadcastChannel(): void
    {
        $provider = new BroadcastChannelProvider();

        $this->assertEquals(['broadcast'], $provider->provides());
    }

    /**
     * Test register adds broadcast channel to registry
     *
     * @return void
     */
    public function testRegisterAddsChannelToRegistry(): void
    {
        $provider = new BroadcastChannelProvider();
        $registry = new ChannelRegistry();

        $provider->register($registry);

        $this->assertTrue($registry->has('broadcast'));
        $channel = $registry->get('broadcast');
        $this->assertInstanceOf(BroadcastChannel::class, $channel);
    }

    /**
     * Test register uses configuration from Configure
     *
     * @return void
     */
    public function testRegisterUsesConfiguration(): void
    {
        Configure::write('Notification.channels.broadcast', [
            'custom' => 'config',
        ]);

        $provider = new BroadcastChannelProvider();
        $registry = new ChannelRegistry();

        $provider->register($registry);

        $this->assertTrue($registry->has('broadcast'));

        Configure::delete('Notification.channels.broadcast');
    }

    /**
     * Test getDefaultConfig returns empty array
     *
     * @return void
     */
    public function testGetDefaultConfigReturnsEmptyArray(): void
    {
        $provider = new BroadcastChannelProvider();

        $this->assertEquals([], $provider->getDefaultConfig());
    }
}
