<?php

class QueueTest extends PHPUnit_Framework_TestCase {
    
    public function testQueueCatchingException() {
        $this->setExpectedException(\yii\base\Exception::class);
        $queue = Yii::createObject([
            'class' => '\vm\queue\Queues\MemoryQueue'
        ]);
         
        /* @var $queue \vm\queue\Queues\MemoryQueue */
         $queue->post(new vm\queue\Job([
             'route' => function() {
                throw new \Exception('Test');
             }
         ]));
         $this->assertEquals(1, $queue->getSize());
         $job = $queue->fetch();
         $this->assertEquals(0, $queue->getSize());
         $queue->run($job);
    }
}


