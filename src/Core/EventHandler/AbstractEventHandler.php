<?php

namespace ABSCore\Core\EventHandler;

abstract class AbstractEventHandler
{
    public function attach($event, $eventManager, $priority)
    {
        $eventManager->attach($event, [$this, 'invoke'], $priority);
    }

    abstract public function invoke($event);
}
