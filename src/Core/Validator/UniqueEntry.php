<?php

namespace ABSCore\Core\Validator;

use Zend\Validator\AbstractValidator;

use ABSCore\DataAccess\DataAccessInterface;

class UniqueEntry extends AbstractValidator
{

    const MSG_NOT_UNIQUE = 'msgNotUnique';

    protected $messageTemplates = array(
        self::MSG_NOT_UNIQUE => '%values% are already exists, and cannot be duplicated'
    );

    protected $values = '';

    protected $messageVariables = array('values' => 'values');

    protected $dataAccess;

    protected $fields;

    protected $ignoreField = null;

    public function setDataAccess(DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
        return $this;
    }

    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setIgnoreField($field)
    {
        $this->ignoreField = $field;
        return $this;
    }

    public function getIgnoreField()
    {
        return $this->ignoreField;
    }

    public function isValid($value, array $context = null)
    {
        $dataAccess = $this->getDataAccess();
        if (is_null($dataAccess)) {
            throw new \RuntimeException('DataAccess must be set!');
        }

        $fields = $this->getFields();
        if (empty($fields)) {
            throw new \RuntimeException('Fields must be set!');
        }


        $data = array();
        foreach ($fields as $key => $field) {
            if (!array_key_exists($field,$context)) {
                return false;
            }
            if (is_numeric($key)) {
                $key = $field;
            }
            $data[$key] = $context[$field];
        }

        $ignoreField = $this->getIgnoreField();
        if (array_key_exists($ignoreField, $context) && !empty($context[$ignoreField])) {
            $data[] = "$ignoreField != {$context[$ignoreField]}";
        }

        $info = current($dataAccess->fetchAll($data, array('paginated' => false))->toArray());
        if ($info) {
            $this->values = implode($data);
            $this->error(self::MSG_NOT_UNIQUE);
            return false;
        }

        return true;
    }
}
