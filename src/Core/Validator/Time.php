<?php

namespace ABSCore\Core\Validator;

use Zend\Validator\AbstractValidator;

class Time extends AbstractValidator
{

    const MSG_INVALID_TIME = 'msgInvalidTime';

    protected $messageTemplates = [
        self::MSG_INVALID_TIME => 'Invalid time'
    ];

    public function isValid($value, array $context = null)
    {
        $regex = '/^\d{2}:[0-5][0-9]$/';
        if (preg_match($regex, $value) === 0) {
            $this->error(self::MSG_INVALID_TIME);
            return false;
        }
        return true;
    }
}
