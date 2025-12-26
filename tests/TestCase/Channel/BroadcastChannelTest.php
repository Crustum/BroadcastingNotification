<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Test\TestCase\Channel;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Broadcasting\Broadcasting;
use Crustum\BroadcastingNotification\Channel\BroadcastChannel;
use ReflectionClass;
use TestApp\Model\Entity\Admin;
use TestApp\Model\Entity\User;

/**
 * BroadcastChannel Test Case
 *
 * Tests the broadcast channel functionality for sending real-time notifications
 */
class BroadcastChannelTest extends TestCase
{
    /**
     * Test that send broadcasts notification data
     *
     * @return void
     */
    public function testSendBroadcastsNotification(): void
    {
        Broadcasting::setConfig('default', [
            'className' => 'Crustum/Broadcasting.Null',
            'queue' => [
                'connection' => 'default',
                'queue' => 'notifications',
            ],
        ]);

        $channel = new BroadcastChannel();
        $notification = new TestBroadcastNotification();
        $notification->setId('test-id-123');

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $result = $channel->send($entity, $notification);

        $this->assertNull($result);
        Broadcasting::drop('default');
    }

    /**
     * Test that getEventName returns custom name from broadcastAs method
     *
     * @return void
     */
    public function testGetEventNameUsesCustomMethod(): void
    {
        $channel = new BroadcastChannel();
        $notification = new TestBroadcastNotification();

        $reflection = new ReflectionClass($channel);
        $method = $reflection->getMethod('getEventName');
        $method->setAccessible(true);

        $eventName = $method->invoke($channel, $notification);

        $this->assertEquals('notification.test.broadcast', $eventName);
    }

    /**
     * Test that getNotifiableChannel generates correct channel name
     *
     * @return void
     */
    public function testGetNotifiableChannelGeneratesCorrectName(): void
    {
        $channel = new BroadcastChannel();

        $entity = new Entity(['id' => 123]);
        $entity->setSource('Users');

        $reflection = new ReflectionClass($channel);
        $method = $reflection->getMethod('getNotifiableChannel');
        $method->setAccessible(true);

        $channelName = $method->invoke($channel, $entity);

        $this->assertEquals('Cake.ORM.Entity.123', $channelName);
    }

    /**
     * Test that getNotifiableChannel uses receivesBroadcastNotificationsOn when available
     *
     * @return void
     */
    public function testGetNotifiableChannelUsesReceivesBroadcastNotificationsOn(): void
    {
        $channel = new BroadcastChannel();

        $user = new User(['id' => 456]);
        $user->setSource('Users');

        $reflection = new ReflectionClass($channel);
        $method = $reflection->getMethod('getNotifiableChannel');
        $method->setAccessible(true);

        $channelName = $method->invoke($channel, $user);

        $this->assertEquals('users.456', $channelName);
    }

    /**
     * Test that getNotifiableChannel uses Admin entity
     *
     * @return void
     */
    public function testGetNotifiableChannelUsesAdminEntity(): void
    {
        $channel = new BroadcastChannel();

        $user = new Admin(['id' => 123]);
        $user->setSource('Users');

        $reflection = new ReflectionClass($channel);
        $method = $reflection->getMethod('getNotifiableChannel');
        $method->setAccessible(true);

        $channelName = $method->invoke($channel, $user);

        $this->assertEquals('TestApp.Model.Entity.Admin.123', $channelName);
    }

    /**
     * Test that static getNotifiableChannelName generates correct channel name
     *
     * @return void
     */
    public function testGetNotifiableChannelNameStaticGeneratesCorrectName(): void
    {
        $entity = new Entity(['id' => 789]);
        $entity->setSource('Users');

        $channelName = BroadcastChannel::getNotifiableChannelName($entity);

        $this->assertEquals('Cake.ORM.Entity.789', $channelName);
    }

    /**
     * Test that static getNotifiableChannelName uses receivesBroadcastNotificationsOn
     *
     * @return void
     */
    public function testGetNotifiableChannelNameStaticUsesReceivesBroadcastNotificationsOn(): void
    {
        $user = new User(['id' => 999]);
        $user->setSource('Users');

        $channelName = BroadcastChannel::getNotifiableChannelName($user);

        $this->assertEquals('users.999', $channelName);
    }
}
