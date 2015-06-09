<?php

namespace ABSCore\Core\Filter;

use Zend\Filter\FilterInterface;

class Active implements FilterInterface
{
    public function filter($value)
    {
        if (is_null($value)) {
            $value = true;
        } else {
            $value = (bool)$value;
        }
        return $value;
    }
}
