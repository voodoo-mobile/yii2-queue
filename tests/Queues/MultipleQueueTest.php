<?php

class MultipleQueueTest extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $queue = Yii::createObject([
            'class'    => '\vm\queue\Queues\MultipleQueue',
            'queues'   => [
                [
                    'class' => '\vm\queue\Queues\MemoryQueue',
                ],
                [
                    'class' => '\vm\queue\Queues\MemoryQueue',
                ],
                [
                    'class' => '\vm\queue\Queues\MemoryQueue',
                ],
                [
                    'class' => '\vm\queue\Queues\MemoryQueue',
                ],
            ],
            'strategy' => [
                'class' => 'vm\queue\Strategies\RandomStrategy',
            ],
        ]);

        $this->assertTrue($queue instanceof vm\queue\Queues\MultipleQueue);
        /* @var $queue vm\queue\Queues\MultipleQueue */
        $this->assertCount(4, $queue->queues);
        foreach ($queue->queues as $tqueue) {
            $this->assertTrue($tqueue instanceof \vm\queue\Queues\MemoryQueue);
        }
        $this->assertTrue($queue->strategy instanceof \vm\queue\Strategies\Strategy);
        $this->assertTrue($queue->strategy instanceof \vm\queue\Strategies\RandomStrategy);

        $queue0 = $queue->getQueue(0);
        $this->assertTrue($queue0 instanceof \vm\queue\Queues\MemoryQueue);
        $queue4 = $queue->getQueue(4);
        $this->assertNull($queue4);

        $njob = $queue->strategy->fetch();
        $this->assertFalse($njob);
        $i = 0;
        $queue->post(new \vm\queue\Job([
            'route' => function () use (&$i) {
                $i += 1;
            },
        ]));
        do {
            //this some times will exist
            $fjob1 = $queue->fetch();
        } while ($fjob1 == false);
        $this->assertTrue($fjob1 instanceof \vm\queue\Job);
        /* @var $fjob1 vm\queue\Job */
        $index = $fjob1->header[\vm\queue\Queues\MultipleQueue::HEADER_MULTIPLE_QUEUE_INDEX];
        $this->assertContains($index, range(0, 3));
        $fjob1->runCallable();
        $this->assertEquals(1, $i);

        $queue->postToQueue(new \vm\queue\Job([
            'route' => function () use (&$i) {
                $i += 1;
            },
        ]), 3);

        do {
            //this some times will exist
            $fjob2 = $queue->fetch();
        } while ($fjob2 == false);
        $this->assertTrue($fjob2 instanceof \vm\queue\Job);
        $index2 = $fjob2->header[\vm\queue\Queues\MultipleQueue::HEADER_MULTIPLE_QUEUE_INDEX];
        $this->assertEquals(3, $index2);
        $fjob2->runCallable();
        $this->assertEquals(2, $i);
    }
}
