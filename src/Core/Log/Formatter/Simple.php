<?php

namespace ABSCore\Core\Log\Formatter;

/**
 * Simple Formatter
 *
 * This explode extra params in subparams
 * For example:
 *      %param% is an alias to ['extra' => ['param' => 'value']]
 *      %param.subParam% is an alias to ['extra' => ['param' => [ 'subParam' => 'value']]]
 *
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class Simple extends \Zend\Log\Formatter\Base
{

    /**
     * Format of log message
     *
     * @var string
     * @access protected
     */
    protected $stringFormat =  '%timestamp% %priorityName%[%requestId%] %class%:%line% %message%';

    /**
     * Define the string format
     *
     * @param mixed $format
     * @access public
     * @return Simple
     */
    public function setStringFormat($format)
    {
        $this->stringFormat = (string)$format;
        return $this;
    }

    /**
     * Get string format
     *
     * @access public
     * @return string
     */
    public function getStringFormat()
    {
        return $this->stringFormat;
    }

    /**
     * Format a log event
     *
     * @param mixed $event
     * @access public
     * @return string
     */
    public function format($event)
    {
        $output = $this->getStringFormat();
        $event = parent::format($event);
        foreach ($event as $name => $value) {
            if ($name == 'extra') {
                foreach($value as $key => $extraValue) {
                    $aux = $this->addExtraValue($output, $key, $extraValue);
                    if ($aux) {
                        $output = $aux;
                    }
                }
            } else {
                $output = str_replace("%$name%", $value, $output);
            }
        }
        return $output;
    }

    /**
     * Add an extra value to output
     *
     * @param string $output String Format
     * @param string $key    Identifier of extra value
     * @param mixed $value   Value of param
     * @access protected
     * @return string Output replaced | false If extra param doesn't exists into string format
     */
    protected function addExtraValue($output, $key, $value) {
        $result = false;
        if (is_array($value)) {
            foreach ($value as $name => $subValue) {
                $aux = $this->addExtraValue($output, "$key.$name", $subValue);
                if ($aux) {
                    $result = $output = $aux;
                }
            }
        }

        if (!$result) {
            if (strpos($output, "%$key%") !== false) {
                $result = str_replace("%$key%", $this->normalize($value), $output);
            }
        }

        return $result;
    }
}
