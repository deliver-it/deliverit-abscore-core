<?php

namespace ABSCore\Core\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use ABSCore\DataAccess\DataAccessInterface;
use ABSCore\Core\Exception;

/**
 * Abastract class for data services
 */
abstract class AbstractDataService implements DataServiceInterface
{

    // Trait of service manager
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Flag to set if is soft delete
     *
     * @var mixed
     */
    protected $isSoftDelete = true;

    /**
     * Flag to set if should search active entries
     *
     * @var bool
     */
    protected $isSearchByActive = true;

    /**
     * Data access object
     *
     * @var DataAccessInterface
     * @access protected
     */
    protected $dataAccess;

    /**
     * Set of loaded forms
     *
     * @var array
     * @access protected
     */
    protected $forms = [];

    /**
     * Service of permissions control
     * @var PermissionsInterface
     * @access protected
     */
    protected $permissions = null;

    /**
     * Role permission identifier
     * @var string
     * @access protected
     */
    protected $identifier;

    /**
     * Class constructor
     *
     * @param DataAccessInterface $dataAccess
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $identifier
     */
    public function __construct(DataAccessInterface $dataAccess, ServiceLocatorInterface $serviceLocator,
                               $identifier)
    {
        $this->setDataAccess($dataAccess)
            ->setServiceLocator($serviceLocator)
            ->setIdentifier($identifier);
        $this->init();
    }

    /**
     * Method to execute somethings after constructor
     *
     * @access public
     * @return null
     */
    public function init() {}

    /**
     * Abastract method to load form by identifier
     *
     * @access protected
     * @param string $label
     * @return mixed
     */
    abstract protected function loadForm($label);

    /**
     * Set data access object
     *
     * @access public
     * @param DataAccessInterface $dataAccess
     * @return AbstractDataService
     */
    public function setDataAccess(DataAccessInterface $dataAccess) {
        $this->dataAccess = $dataAccess;
        return $this;
    }

    /**
     * Set permissions service
     *
     * @access public
     * @param PermissionsInterface $permissions
     * @return AbstractDataService
     */
    public function setPermissions(PermissionsInterface $permissions)
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Get permissions service
     *
     * @access public
     * @return PermissionsInterface
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Get data access object
     *
     * @access public
     * @return DataAccessInterface
     */
    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    /**
     * Get role permission identifier
     *
     * @access public
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Retrieve list of elements
     *
     * @access public
     * @param array $where
     * @param array $params
     * @return Zend\Db\ResultSet\ResultSet | Zend\Paginator\Paginator
     */
    public function fetchAll($where = null, $params = array())
    {
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'retrieve_list')) {
            throw new Exception\UnauthorizedException('You cannot retrieve list of '. $this->getIdentifier());
        }
        if (is_null($where)) {
            $where = array();
        }
        if (
            is_array($where) && !array_key_exists('active', $where) &&
            (!array_key_exists('showInactive', $params) || !$params['showInactive']) && $this->isSearchByActive()
        ) {
            $where['active'] = 1;
            $params['active'] = 1;
        }
        $data = $this->getDataAccess()->fetchAll($where, $params);

        return $data;
    }

    public function isSoftDelete()
    {
        return $this->isSoftDelete;
    }

    public function setIsSoftDelete($flag)
    {
        $this->isSoftDelete = (bool) $flag;
        return $this;
    }

    public function setSearchByActive($flag)
    {
        $this->isSearchByActive = (bool) $flag;
        return $this;
    }

    public function isSearchByActive()
    {
        return $this->isSearchByActive;
    }

    /**
     * Retrieve an element by id
     *
     * @access public
     * @param mixed $id
     * @return mixed
     */
    public function find($id)
    {
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'retrieve')) {
            throw new Exception\UnauthorizedException('You cannot retrieve '. $this->getIdentifier());
        }
        $data = $this->getDataAccess()->find($id);

        return $data;
    }

    /**
     * Create or update element
     *
     * @access public
     * @param mixed $id
     * @param array $data
     * @return boolean
     */
    public function save($id, $data)
    {
        if (!is_array($data)) {
            throw new Exception\RuntimeException('Data must be an array');
        }

        $dataAccess = $this->getDataAccess();

        $key = current($this->getDataAccess()->getPrimaryKey());

        if (is_null($id)) {
            unset($data[$key]);
            $permission = 'create';
        } else {
            $permission = 'update';
            $data[$key] = $id;
        }
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), $permission, $data)) {
            throw new Exception\UnauthorizedException('You cannot '. $permission . ' '. $this->getIdentifier());
        }

        try {
            $dataAccess->save($data);
            if (!$id) {
                $id = $dataAccess->getTableGateway()->getLastInsertValue();
            }
            return $id;
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Element soft delete
     *
     * @access public
     * @param mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'delete')) {
            throw new Exception\UnauthorizedException('You cannot delete '. $this->getIdentifier());
        }
        $dataAccess = $this->getDataAccess();
        $data = (array)$dataAccess->find($id);
        if ($this->isSoftDelete()) {
            $data['active'] = 0;
            return $dataAccess->save($data);
        } else {
            return $dataAccess->delete(['id' => $id]);
        }
    }

    /**
     * Get form by identifier
     *
     * @access public
     * @param string $label
     * @return mixed
     */
    public function getForm($label)
    {
        $label = (string)$label;
        if (!array_key_exists($label, $this->forms)) {
            $this->forms[$label] = $this->loadForm($label);
        }
        return $this->forms[$label];
    }

    /**
     * Set role permission identifier
     *
     * @access public
     * @param string $identifier
     * @return AbstractDataService
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = (string)$identifier;
        return $this;
    }

}
