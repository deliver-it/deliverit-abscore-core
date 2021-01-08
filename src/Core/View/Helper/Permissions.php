<?php
namespace ABSCore\Core\View\Helper;

use ABSCore\Core\Service;

use Zend\View\Helper\AbstractHelper;

class Permissions extends AbstractHelper
{
    public function __construct(Service\PermissionsInterface $permissions)
    {
        $this->setPermissionsService($permissions);
    }

    public function __invoke()
    {
        return $this;
    }

    public function setPermissionsService(Service\PermissionsInterface $permissions)
    {
        $this->permissionsService = $permissions;
        return $this;
    }

    public function getPermissionsService()
    {
        return $this->permissionsService;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->getPermissionsService(), $method), $args);
    }
}
