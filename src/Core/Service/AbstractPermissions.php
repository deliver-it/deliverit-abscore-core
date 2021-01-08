<?php 
namespace ABSCore\Core\Service;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

use ABSCore\Core\Model;
use ABSCore\Core\Exception;


/**
 * Model to manage system permissions
 */
abstract class AbstractPermissions implements PermissionsInterface
{

    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Access control list
     *
     * @var \Zend\Permissions\Acl\Acl;
     * @access protected
     */
    protected $acl;

    /**
     * User identity
     *
     * @var mixed
     * @access protected
     */
    protected $identity;

    /**
     * Class contructor
     *
     * @param mixed $service
     * @param mixed $identity
     * @access public
     */
    public function __construct($service, Model\IdentityInterface $identity)
    {
        $this->setServiceLocator($service)
            ->setIdentity($identity)
            ->init();
    }

    /**
     * Define user identity
     *
     * @param mixed $identity
     * @access public
     * @return Permissions
     */
    public function setIdentity(Model\IdentityInterface $identity)
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * Get identity
     *
     * @access public
     * @return mixed
     */
    public function getIdentity() {
        return $this->identity;
    }

    /**
     * Define Service locator
     *
     * @param mixed $service
     * @access public
     * @return Permissions
     */
    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Initialize permissions
     *
     * @access protected
     */
    abstract protected function init();

    /**
     * Create new acl object
     *
     * @access protected
     * @return Permissions
     */
    protected function createAcl()
    {
        $acl = new Acl();
        $this->acl = $acl;
        return $this;
    }

    /**
     * Get ACL object
     *
     * @access public
     * @return \Zend\Permissions\Acl
     */
    public function getAcl()
    {
        if ($this->acl == null) {
            $this->createAcl();
        }
        return $this->acl;
    }


    /**
     * Verify if user is allowed
     *
     * @param string $identifier
     * @param string $method
     * @param array $params Optional params used into custom
     * @access public
     * @return boolean
     */
    public function isAllowed($identifier, $method, array $params = [])
    {
        $methodName = 'isAllowed'.$identifier.$method;
        if (method_exists($this, $methodName)) {
            return $this->$methodName($params);
        }

        return $this->isAllowedAcl($identifier, $method);
    }

    /**
     * Verify if user is allowed into ACL
     *
     * @param string $identifier
     * @param string $method
     * @access protected
     * @return boolean
     */
    protected function isAllowedAcl($identifier, $method)
    {
        $groups = $this->getIdentity()->getGroups();
        if (!is_array($groups)) {
            throw new Exception\RuntimeException('Identity groups must be an array');
        }
        foreach ($groups as $group) {
            if ($this->getAcl()->isAllowed($group, $identifier, $method)) {
                return true;
            }
        }
        return false;
    }
}