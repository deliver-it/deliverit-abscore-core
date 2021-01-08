<?php

namespace ABSCore\Core\Db\Adapter\Profiler;

/**
 * Query profiler
 *
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class Profiler extends \Zend\Db\Adapter\Profiler\Profiler
{

    /**
     * Logger object
     *
     * @var \Zend\Log\LoggerInterface
     * @access protected
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param \Zend\Log\LoggerInterface $logger
     * @access public
     */
    public function __construct(\Zend\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Callback when query finish
     *
     * @access public
     * @return null
     */
    public function profilerFinish()
    {
        parent::profilerFinish();
        $profile = current($this->profiles);
        $data = array();
        foreach ((array)$profile['parameters']->getNamedArray() as $key => $value) {
            $data[] = "$key = $value";
        }
        $msg = 'Sql: ' . $profile['sql'];
        $msg .= ' | Data: '.implode(',', $data);
        $msg .= ' | Time: '. round($profile['elapse'], 4);
        $this->logger->debug($msg);

        $this->profiles = array();
        $this->currentIndex = 0;
    }
}
