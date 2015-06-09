<?php
namespace ABSCore\Core;

class Module
{

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/Core',
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'ABSCore\Core\Logger' => function ($sm) {
                    $config = $sm->get('config');
                    $logFile = $config['log']['file'];
                    $priority = $config['log']['priority'];

                    $writer = new \Zend\Log\Writer\Stream($logFile);

                    $formatter = new Log\Formatter\Simple();
                    $writer->setFormatter($formatter);

                    $filter = new \Zend\Log\Filter\Priority($priority);
                    $writer->addFilter($filter);

                    $logger = new \Zend\Log\Logger(array(
                        'processors' => array(
                            array('name' => 'RequestId'),
                            array('name' => 'Backtrace'),
                        )
                    ));
                    $logger->addWriter($writer);

                    return $logger;
                },
                'ABSCore\Core\Db\Adapter\Profiler\Profiler' => function ($sm) {
                    $logger = $sm->get('Logger');
                    return new Db\Adapter\Profiler\Profiler($logger);
                }
            )
        );
    }

    public function getControllerPluginConfig()
    {
        return array(
            'invokables' => array(
                'ABSCore\Core\Controller\Plugin\Permissions' => 'ABSCore\Core\Controller\Plugin\Permissions',
            ),
        );
    }
}
