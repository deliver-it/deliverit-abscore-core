<?php

namespace ABSCore\Core\EventHandler;

/**
 * AbstractEventHandler
 */
abstract class AbstractEventHandler
{
    /**
     * Attach this into event manager when event happens
     *
     * @param mixed $event
     * @param mixed $eventManager
     * @param mixed $priority
     * @access public
     * @return $this
     */
    public function attach($event, $eventManager, $priority)
    {
        $eventManager->attach($event, [$this, 'invoke'], $priority);

        return $this;
    }

    abstract public function invoke($event);
}
