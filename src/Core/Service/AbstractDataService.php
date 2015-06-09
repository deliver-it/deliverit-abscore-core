<?php

namespace ABSCore\Core\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use ABSCore\DataAccess\DataAccessInterface;
use ABSCore\Core\Exception\UnauthorizedException;

abstract class AbstractDataService implements DataServiceInterface
{

    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $dataAccess;
    protected $serviceLocator;
    protected $forms = array();
    protected $options = array();

    protected $identifier;

    public function __construct(DataAccessInterface $dataAccess, ServiceLocatorInterface $serviceLocator,
                               $identifier, PermissionsInterface $permissions = null)
    {
        $this->setDataAccess($dataAccess)
            ->setServiceLocator($serviceLocator)
            ->setPermissions($permissions)
            ->setIdentifier($identifier);
        $this->init();
    }

    public function init() {}

    abstract protected function loadForm($label);

    public function setDataAccess($dataAccess) {
        $this->dataAccess = $dataAccess;
        return $this;
    }

    public function setPermissions(PermissionsInterface $permissions)
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function fetchAll($where = null, $params = array())
    {
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'retrieve_list')) {
            throw new UnauthorizedException('You cannot retrieve list of '. $this->getIdentifier());
        }
        if (is_null($where)) {
            $where = array();
        }
        if (
            is_array($where) && !array_key_exists('active', $where) &&
            (!array_key_exists('showInactive', $params) || !$params['showInactive'])
        ) {
            $where['active'] = 1;
            $params['active'] = 1;
        }
        $this->setOptions($params);
        $data = $this->getDataAccess()->fetchAll($where, $params);
        if ($data instanceof \Zend\Db\ResultSet\ResultSet) {
            $aux = array();
            foreach ($data as $item) {
                $entry = $this->filterData($item, false);
                if ($entry) {
                    $aux[] = $entry;
                }
            }
            $data->initialize($aux);
        } else if ($data instanceof \Zend\Paginator\Paginator) {
            $items = $data->getCurrentItems();
            $aux = array();
            foreach ($items as $item) {
                $entry = $this->filterData($item, false);
                if ($entry) {
                    $aux[] = $entry;
                }
            }
            $items->initialize($aux);
        }
        return $data;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        $name = (string)$name;
        $options =  $this->getOptions();
        $result = null;
        if (array_key_exists($name, $options)) {
            $result = $options[$name];
        }
        return $result;
    }

    public function find($id, array $options = array())
    {
        $this->setOptions($options);
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'retrieve')) {
            throw new UnauthorizedException('You cannot retrieve '. $this->getIdentifier());
        }
        $data = $this->getDataAccess()->find($id);

        return $this->filterData($data, true);
    }

    protected function filterData($data, $onlyOne)
    {
        return $data;
    }

    public function save($id, $data)
    {
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
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), $permission)) {
            throw new UnauthorizedException('You cannot '. $permission . ' '. $this->getIdentifier());
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

    public function delete($id)
    {
        $permissions = $this->getPermissions();
        if (!is_null($permissions) && !$permissions->isAllowed($this->getIdentifier(), 'delete')) {
            throw new UnauthorizedException('You cannot delete '. $this->getIdentifier());
        }
        $dataAccess = $this->getDataAccess();
        $data = (array)$dataAccess->find($id);
        $data['active'] = 0;
        return $dataAccess->save($data);
    }

    public function getForm($label)
    {
        $label = (string)$label;
        if (!array_key_exists($label, $this->forms)) {
            $this->forms[$label] = $this->loadForm($label);
        }
        return $this->forms[$label];
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = (string)$identifier;
        return $this;
    }

}
