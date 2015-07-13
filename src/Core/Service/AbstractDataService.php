<?php

namespace ABSCore\Core\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Paginator\Paginator;
use ABSCore\DataAccess;
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
     * @var DataAccess\DataAccessInterface
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
     * Field name to check if registry is active (soft delete)
     *
     * @var string
     */
    protected $activeField = 'active';

    /**
     * Class constructor
     *
     * @param DataAccess\DataAccessInterface $dataAccess
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $identifier
     */
    public function __construct(DataAccess\DataAccessInterface $dataAccess, ServiceLocatorInterface $serviceLocator,
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
     * @param DataAccess\DataAccessInterface $dataAccess
     * @return AbstractDataService
     */
    public function setDataAccess(DataAccess\DataAccessInterface $dataAccess) {
        $this->dataAccess = $dataAccess;
        return $this;
    }

    /**
     * setActiveField
     *
     * @param string $activeField
     * @return void
     */
    public function setActiveField($activeField)
    {
        $this->activeField = $activeField;
    }

    /**
     * getActiveField
     *
     * @return string
     */
    public function getActiveField()
    {
        return $this->activeField;
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
     * @return DataAccess\DataAccessInterface
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
        $activeField = $this->getActiveField();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'retrieve_list')) {
            throw new Exception\UnauthorizedException('You cannot retrieve list of '. $this->getIdentifier());
        }
        if (is_null($where)) {
            $where = array();
        }
        if (
            is_array($where) && !array_key_exists($activeField, $where) &&
            (!array_key_exists('showInactive', $params) || !$params['showInactive']) && $this->isSearchByActive()
        ) {
            $where[$activeField] = 1;
            $params[$activeField] = 1;
        }
        $dbQuery = $this->getFetchAllDBQuery($where, $params);
        if (!is_null($dbQuery)) {
            $data = $this->fetchWithDBQuery($dbQuery, $params);
        } else {
            $data = $this->getDataAccess()->fetchAll($where, $params);
        }

        return $data;
    }

    protected function fetchWithDBQuery($dbQuery, $params)
    {
        if (!is_object($dbQuery) || !$dbQuery instanceof DataAccess\DBQuery) {
            throw new Exception\RuntimeException('fetchAll query must be a DBQuery instance');
        }
        if (isset($params['paginated']) && $params['paginated']) {
            $adapter = new DataAccess\Paginator\Adapter\DBQuery($dbQuery);
            $paginator = new Paginator($adapter);
            if (isset($params['page'])) {
                $paginator->setCurrentPageNumber($params['page']);
            }

            if (isset($params['perPage'])) {
                $paginator->setItemCountPerPage($params['perPage']);
            }

            $result = $paginator;
        } else {
            $result = $dbQuery->fetch();
        }
        return $result;
    }

    protected function getFetchAllDBQuery($where, $params)
    {
        return null;
    }

    protected function getFindDBQuery($id)
    {
        return null;
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
        $dbQuery = $this->getFindDBQuery($id);
        if (!is_null($dbQuery)) {
            $data = $dbQuery->fetch()->current();
            if (is_null($data)) {
                throw new DataAccess\Exception\UnknowRegistryException('Registry not found.');
            }
        } else {
            $data = $this->getDataAccess()->find($id);
        }

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
        $activeField = $this->getActiveField();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'delete')) {
            throw new Exception\UnauthorizedException('You cannot delete '. $this->getIdentifier());
        }
        $dataAccess = $this->getDataAccess();
        $data = (array)$dataAccess->find($id);
        if ($this->isSoftDelete()) {
            $data[$activeField] = 0;
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
