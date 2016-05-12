<?php
/**
 * DeferredEventInterface interface file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace vm\queue\Behaviors;

/**
 * DeferredEventInterface provides method interface for handling the deferred
 * event.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
interface DeferredEventInterface
{

    /**
     * @param string $eventName The name of the event.
     * @return void
     */
    public function handleDeferredEvent($eventName);
}
