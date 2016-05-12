<?php
/**
 * MultipleQueue class file.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since  2015.02.25
 */

namespace vm\queue\Queues;

use vm\queue\Job;
use vm\queue\Queue;
use vm\queue\Strategies\Strategy;
use vm\queue\Strategies\RandomStrategy;

/**
 * MultipleQueue is a queue abstraction that handles multiple queue at once.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since  2015.02.25
 */
class MultipleQueue extends Queue
{
    /**
     * Additional header for the job.
     */
    const HEADER_MULTIPLE_QUEUE_INDEX = 'MultipleQueueIndex';

    /**
     * Stores the queue.
     * @var Queue[]
     */
    public $queues = [];

    /**
     * The job fetching strategy.
     * @var \vm\queue\Strategies\Strategy
     */
    public $strategy = ['class' => RandomStrategy::class];

    /**
     * Initialize the queue.
     * @return void
     * @throws \yii\base\InvalidConfigException If the strategy doesn't implement
     * vm\queue\Strategies\Strategy.
     */
    public function init()
    {
        parent::init();
        $queueObjects = [];
        foreach ($this->queues as $id => $queue) {
            $queueObjects[$id] = \Yii::createObject($queue);
        }
        $this->queues = $queueObjects;
        if (is_array($this->strategy)) {
            $this->strategy = \Yii::createObject($this->strategy);
        } else if ($this->strategy instanceof Strategy) {
            throw new \yii\base\InvalidConfigException(
                'The strategy field have to implement vm\queue\Strategies\Strategy'
            );
        }
        $this->strategy->setQueue($this);
    }

    /**
     * @param integer $index The index of the queue.
     *
     * @return Queue|null the queue or null if not exists.
     */
    public function getQueue($index)
    {
        return \yii\helpers\ArrayHelper::getValue($this->queues, $index);
    }

    /**
     * Delete the job.
     *
     * @param Job $job The job.
     *
     * @return boolean Whether the operation succeed.
     */
    protected function deleteJob(Job $job)
    {
        return $this->strategy->delete($job);
    }

    /**
     * Return next job from the queue.
     * @return Job|boolean The job fetched or false if not found.
     */
    protected function fetchJob()
    {
        return $this->strategy->fetch();
    }

    /**
     * Post new job to the queue.
     *
     * @param Job $job The job.
     *
     * @return boolean Whether operation succeed.
     */
    protected function postJob(Job $job)
    {
        return $this->postToQueue($job, 0);
    }

    /**
     * Post new job to a specific queue.
     *
     * @param Job     $job   The job.
     * @param integer $index The queue index.
     *
     * @return boolean Whether operation succeed.
     */
    public function postToQueue(Job &$job, $index)
    {
        $queue = $this->getQueue($index);
        if ($queue === null) {
            return false;
        }

        return $queue->post($job);
    }

    /**
     * Release the job.
     *
     * @param Job $job The job to release.
     *
     * @return boolean whether the operation succeed.
     */
    protected function releaseJob(Job $job)
    {
        $index = $job->header[self::HEADER_MULTIPLE_QUEUE_INDEX];
        $queue = $this->getQueue($index);

        return $queue->release($job);
    }

    /**
     * Returns the total number of all queue size.
     * @return integer
     */
    public function getSize()
    {
        return array_sum(array_map(function (Queue $queue) {
            return $queue->getSize();
        }, $this->queues));
    }

    /**
     * Purge the whole queue.
     * @return boolean
     */
    public function purge()
    {
        foreach ($this->queues as $queue) {
            $queue->purge();
        }

        return true;
    }
}
