<?php

namespace ABSCore\Core\Validator;

use Zend\Validator\AbstractValidator;
use ABSCore\DataAccess\DataAccessInterface;
use ABSCore\DataAccess\Exception\UnknowRegistryException;

class Exists extends AbstractValidator
{
    protected $dataAccess;

    protected $entries = array();

    const MSG_NOT_EXISTS = 'msgNotExists';

    protected $messageTemplates = array(
        self::MSG_NOT_EXISTS => '%identifier% not exists'
    );

    protected $identifier = '';

    protected $messageVariables = array('identifier' => 'identifier');

    public function setDataAccess(DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
        return $this;
    }

    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = (string)$identifier;
        return $this;
    }

    public function isValid($value, array $context = null)
    {
        $entry = $this->getEntry($value);
        $result = ($entry ? (bool) $entry['active']: false);
        if (!$result) {
            $this->error(self::MSG_NOT_EXISTS);
        }
        return $result;
    }

    protected function getEntry($value)
    {
        if (!array_key_exists($value, $this->entries)) {
            try {
                $dataAccess = $this->getDataAccess();
                if (is_null($dataAccess)) {
                    throw new \RuntimeException('DataAccess must be set!');
                }
                $entry = $dataAccess->find($value);
            } catch (UnknowRegistryException $e) {
                $entry = null;
            }
            $this->entries[$value] = $entry;
        }
        return $this->entries[$value];
    }
}
