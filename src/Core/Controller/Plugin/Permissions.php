<?php

namespace ABSCore\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Plugin to permissions actions into controllers
 *
 * @uses AbstractPlugin
 * @author Luiz Cunha <luiz.felipe@absoluta.net>
 */
class Permissions extends AbstractPlugin
{

    protected $permissionService;

    public function __construct(ABSCore\Core\Service\PermissionsInterface $permissions) {
        $this->permissionService = $permission;
    }

    /**
     * Function that redirect flow if user don't have permission to access method resource
     *
     * @param string $identifier  Resource Identifier
     * @param string $method      Method of resource
     * @param string $route       Route to redirect
     * @param array $routeParams  Route params
     * @access public
     * @return boolean true if redirect | false otherwise
     */
    public function ifNotAllowedGoTo($identifier, $method, $route = 'home', $routeParams = array())
    {
        $controller = $this->getController();
        if (!$this->permissionService->isAllowed($identifier, $method)) {
           $controller->flashMessenger()->addErrorMessage('You don\'t have permission!');
           $controller->redirect()->toRoute($route, $routeParams);
           return true;
        }
        return false;
    }
}
