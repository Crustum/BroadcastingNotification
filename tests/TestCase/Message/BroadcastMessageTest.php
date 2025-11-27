<?php
declare(strict_types=1);

namespace Crustum\BroadcastingNotification\Test\TestCase\Message;

use Cake\TestSuite\TestCase;
use Crustum\BroadcastingNotification\Message\BroadcastMessage;

/**
 * BroadcastMessage Test Case
 */
class BroadcastMessageTest extends TestCase
{
    /**
     * Test constructor sets data
     *
     * @return void
     */
    public function testConstructorSetsData(): void
    {
        $data = ['message' => 'Hello', 'user_id' => 123];
        $message = new BroadcastMessage($data);

        $this->assertEquals($data, $message->getData());
    }

    /**
     * Test data method sets and returns data
     *
     * @return void
     */
    public function testDataMethodSetsData(): void
    {
        $message = new BroadcastMessage();
        $data = ['event' => 'test'];

        $result = $message->data($data);

        $this->assertSame($message, $result);
        $this->assertEquals($data, $message->getData());
    }

    /**
     * Test onConnection sets connection
     *
     * @return void
     */
    public function testOnConnectionSetsConnection(): void
    {
        $message = new BroadcastMessage();
        $result = $message->onConnection('redis');

        $this->assertSame($message, $result);
        $this->assertEquals('redis', $message->getConnection());
    }

    /**
     * Test onQueue sets queue
     *
     * @return void
     */
    public function testOnQueueSetsQueue(): void
    {
        $message = new BroadcastMessage();
        $result = $message->onQueue('broadcasts');

        $this->assertSame($message, $result);
        $this->assertEquals('broadcasts', $message->getQueue());
    }

    /**
     * Test toArray returns data
     *
     * @return void
     */
    public function testToArrayReturnsData(): void
    {
        $data = ['title' => 'Test', 'body' => 'Message'];
        $message = new BroadcastMessage($data);

        $this->assertEquals($data, $message->toArray());
    }

    /**
     * Test fluent interface
     *
     * @return void
     */
    public function testFluentInterface(): void
    {
        $message = (new BroadcastMessage(['initial' => 'data']))
            ->data(['updated' => 'data'])
            ->onConnection('redis')
            ->onQueue('high-priority');

        $this->assertEquals(['updated' => 'data'], $message->getData());
        $this->assertEquals('redis', $message->getConnection());
        $this->assertEquals('high-priority', $message->getQueue());
    }
}
