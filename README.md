# Queue Component for Yii2

This provides queue component for Yii2.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist vm/yii2-queue "^1.4.0"
```

or add

```
"vm/yii2-queue": "^1.4.0"
```

to the require section of your `composer.json` file.

To use Redis queue or RabbitMQ, you have to add `yiisoft/yii2-redis:*` or
`videlalvaro/php-amqplib: 2.5.*` respectively.

## Setting Up

After the installation, first step is to set the console controller.

```php
return [
    // ...
    'controllerMap' => [
        'queue' => [
            'class' => 'vm\queue\Console\Controller',
            //'sleepTimeout' => 1
        ],
        
    ],
];
```

For the task worker, set a new module, e.g. `task` and declare it in the config.

```php
'modules' => [
    'task' => [
        'class' => 'app\modules\task\Module',
    ]
]
```

And then set the queue component. Don't forget to set the module name that runs
the task in the component. For example, queue using AWS SQS

```php
'components' => [
    'queue' => [
        'class' => 'vm\queue\Queues\SqsQueue',
        'module' => 'task',
        'url' => 'https://sqs.ap-southeast-1.amazonaws.com/123456789012/queue',
		'config' => [
			'key' => 'AKIA1234567890123456',
			'secret' => '1234567890123456789012345678901234567890',
			'region' => 'ap-southeast-1',
			'version' => 'latest'
        ],
    ]
]
```

Or using Database queue

```php
'components' => [
    'db' => [
        //the db component
    ],
    'queue' => [
        'class' => 'vm\queue\Queues\DbQueue',
        'db' => 'db',
        'tableName' => 'queue',
        'module' => 'task',
    ]
]
```

## Usage

### Creating A Worker

Creating a worker is just the same with creating console or web controller.
In the task module create a controller that extends `vm\queue\Worker\Controller`

e.g.

```php
class FooController extends vm\queue\Worker\Controller {

    public function actionBar($param1, $param2){
        echo $param1;
    }
}
```

To prevent the job got deleted from the queue, for example when the job is not
completed, return `false` in the action. The job will be run again the next
chance.

e.g.

```php
class FooController extends vm\queue\Worker\Controller {

    public function actionBar($param1, $param2){
        try {
        } catch (\Exception $ex){
            \Yii::error('Ouch something just happened');
            return false;
        }
    }
}
```

### Running The Listener

To run the listener, run the console that set in the above config. If the
controller mapped as `queue` then run.

```
./yii queue/listen
```

### Posting A Job

To post a job from source code, put something like this.

```php
use vm\queue\Job;

$route = 'foo/bar';
$data = ['param1' => 'foo', 'param2' => 'bar'];
Yii::$app->queue->post(new Job(['route' => $route, 'data' => $data]));
```

Job can also be posted from the console. The data in the second parameter is in
JSON string.

```
yii queue/post 'foo/bar' '{"param1": "foo", "param2": "bar"}'
```

Job can also be posted as anonymous function. Be careful using this.

```php
Yii::$app->queue->post(new Job(function(){
    echo 'Hello World!';
}));
```

### Deferred Event

In this queue, there is a feature called **Deferred Event**. Basically using this
feature, we can defer a process executed after a certain event using queue.

To use this, add behavior in a component and implement the defined event handler.

```php
    public function behaviors() {
        return array_merge([
            [
                'class' => \vm\queue\Behaviors\DeferredEventBehavior::class,
                'events' => [
                    self::EVENT_AFTER_VALIDATE => 'deferAfterValidate',
                ]
            ]
        ]);
    }

    public function deferAfterValidate(){
        //Do something here.
    }
```

**NOTE**
Due to reducing the message size, the `$event` object that usually passed when
triggered the event will not be passed to the deferred event. Also, the object
in which the method invoked is merely a clone object, so it won't have the
behavior and the event attached in the original object.

As for `ActiveRecord` class, since the object can not be passed due to limitation
of SuperClosure in serializing PDO (I personally think that's bad too), the
behavior should use `\vm\queue\Behaviors\ActiveRecordDeferredEventBehavior`
instead. The difference is in the object in which the deferred event handler
invoked.

Since we can not pass the original object, the invoking object will be re-fetched
from the table using the primary key. And for the `afterDelete` event, since
the respective row is not in the table anymore, the invoking object is a new
object whose attributes are assigned from the attributes of the original object.

### Web End Point

We can use web endpoint to use the queue by adding `\vm\queue\Web\Controller`
to the controller map.

For example

```php
    'controllerMap' => [
        'queue' => [
            /* @var $queue vm\queue\Web\Controller */
            'class' => 'vm\queue\Web\Controller'
        ]
    ],
```

To post this use

```
curl -XPOST http://example.com/queue/post --data route='test/test' --data data='{"data":"data"}'
```

To limit the access to the controller, we can use `\yii\filters\AccessControl` filter.

For example to filter by IP address, we can use something like this.

```php
    'controllerMap' => [
        'queue' => [
            /* @var $queue vm\queue\Web\Controller */
            'class' => 'vm\queue\Web\Controller',
            'as access' => [
                'class' => '\yii\filters\AccessControl',
                'rules' => [
                    [
                        'allow' => true,
                        'ips' => [
                            '127.0.0.1'
                        ]
                    ]
                ]
            ]
        ]
    ],
```

## Testing

To run the tests, in the root directory execute below.

```
./vendor/bin/phpunit
```

## Road Map

- Add more queue provider such as MemCache, IronMQ.
