<?php
/**
 * SqsQueue class file.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since  2015.02.24
 */

namespace vm\queue\Queues;

use \Aws\Sqs\SqsClient;
use vm\queue\Job;

/**
 * SqsQueue provides queue for AWS SQS.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since  2015.02.24
 */
class SqsQueue extends \vm\queue\Queue
{

    /**
     * The SQS url.
     * @var string
     */
    public $url;

    /**
     * The config for SqsClient.
     * This will be used for SqsClient::factory($config);
     * @var array
     */
    public $config = [];

    /**
     * Due to ability of the queue message to be visible automatically after
     * a certain of time, this is not required.
     * @var boolean
     */
    public $releaseOnFailure = false;

    /**
     * Stores the SQS client.
     * @var \Aws\Sqs\SqsClient
     */
    private $_client;

    /**
     * Initialize the queue component.
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->_client = SqsClient::factory($this->config);
    }

    /**
     * Return next job from the queue.
     * @return Job|boolean the job or false if not found.
     */
    public function fetchJob()
    {
        $message = $this->_client->receiveMessage([
            'QueueUrl'            => $this->url,
            'AttributeNames'      => ['ApproximateReceiveCount'],
            'MaxNumberOfMessages' => 1,
        ]);
        if (isset($message['Messages']) && count($message['Messages']) > 0) {
            return $this->createJobFromMessage($message['Messages'][0]);
        } else {
            return false;
        }
    }

    /**
     * Create job from SQS message.
     *
     * @param array $message The message.
     *
     * @return \vm\queue\Job
     */
    private function createJobFromMessage($message)
    {
        $job                          = $this->deserialize($message['Body']);
        $job->header['ReceiptHandle'] = $message['ReceiptHandle'];
        $job->id                      = $message['MessageId'];

        return $job;
    }

    /**
     * Post the job to queue.
     *
     * @param Job $job The job posted to the queue.
     *
     * @return boolean whether operation succeed.
     */
    public function postJob(Job $job)
    {
        $model = $this->_client->sendMessage([
            'QueueUrl'    => $this->url,
            'MessageBody' => $this->serialize($job),
        ]);
        if ($model !== null) {
            $job->id = $model['MessageId'];

            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the job from the queue.
     *
     * @param Job $job The job to be deleted.
     *
     * @return boolean whether the operation succeed.
     */
    public function deleteJob(Job $job)
    {
        if (!empty($job->header['ReceiptHandle'])) {
            $receiptHandle = $job->header['ReceiptHandle'];
            $response      = $this->_client->deleteMessage([
                'QueueUrl'      => $this->url,
                'ReceiptHandle' => $receiptHandle,
            ]);

            return $response !== null;
        } else {
            return false;
        }
    }

    /**
     * Release the job.
     *
     * @param Job $job The job to release.
     *
     * @return boolean whether the operation succeed.
     */
    public function releaseJob(Job $job)
    {
        if (!empty($job->header['ReceiptHandle'])) {
            $receiptHandle = $job->header['ReceiptHandle'];
            $response      = $this->_client->changeMessageVisibility([
                'QueueUrl'          => $this->url,
                'ReceiptHandle'     => $receiptHandle,
                'VisibilityTimeout' => 0,
            ]);

            return $response !== null;
        } else {
            return false;
        }
    }

    /**
     * Returns the SQS client used.
     * @return \Aws\Sqs\SqsClient
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Returns the number of queue size.
     * @return integer
     */
    public function getSize()
    {
        $response   = $this->getClient()->getQueueAttributes([
            'QueueUrl'       => $this->url,
            'AttributeNames' => [
                'ApproximateNumberOfMessages',
            ],
        ]);
        $attributes = $response->get('Attributes');

        return \yii\helpers\ArrayHelper::getValue($attributes, 'ApproximateNumberOfMessages', 0);
    }

    /**
     * Purge the whole queue.
     * @return boolean
     */
    public function purge()
    {
        $response = $this->getClient()->getQueueAttributes([
            'QueueUrl' => $this->url,
        ]);

        return $response !== null;
    }

}
