<?php

namespace ABSCore\Core\Controller\API;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

use ABSCore\Core\Exception;;

use ABSCore\DataAccess\Exception\UnknowRegistryException;
use ABSCore\Core\Service;

class RestController extends AbstractRestfulController
{

    /**
     * Default Limit of entries on getList
     *
     * @var float
     * @access protected
     */
    protected $limit = 100;

    /**
     * Flag to identify if default listing is paginated
     *
     * @var mixed
     * @access protected
     */
    protected $isDefaultPaginated = true;


    /**
     * Class constructor
     *
     * @param Service\DataServiceInterface $service
     * @param string $singularName
     * @param string $pluralName
     * @access public
     */
    public function __construct(Service\DataServiceInterface $service, $singularName, $pluralName = null)
    {
        $this->setService($service);
        $this->setNames($singularName, $pluralName);
    }

    /**
     * Define the default per page
     *
     * @param int $limit
     * @access public
     * @return RestController
     */
    public function setDefaultLimitPerPage($limit)
    {
        $limit = (int)$limit;
        if ($limit <= 0) {
            throw new Exception\RuntimeException('Invalid limit');
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get default limit per page
     *
     * @access public
     * @return int
     */
    public function getDefaultLimitPerPage()
    {
        return $this->limit;
    }

    /**
     * Define data service
     *
     * @param Service\DataServiceInterface $service
     * @access public
     * @return RestController
     */
    public function setService(Service\DataServiceInterface $service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Get data service
     *
     * @access public
     * @return Service\DataServiceInterface
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set if the default is paginated list
     *
     * @param bool $flag
     * @access public
     * @return RestController
     */
    public function setIsDefaultPaginated($flag)
    {
        $this->isDefaultPaginated = (bool)$flag;
        return $this;
    }

    /**
     * Verify if default listing is paginated
     *
     * @access public
     * @return bool
     */
    public function isDefaultPaginated()
    {
        return $this->isDefaultPaginated;
    }

    /**
     * Verify if current request is paginated
     *
     * @access public
     * @return bool
     */
    public function isPaginated()
    {
        $isDefaultPaginated = $this->isDefaultPaginated();
        $paginated = ((bool)$this->params()->fromQuery('show_all', !$isDefaultPaginated)) === false;
        return $paginated;
    }


    /**
     * Get list of entries
     *
     * @access public
     * @return JsonModel
     */
    public function getList()
    {
        $logger = $this->getServiceLocator()->get('ABSCore\Core\Log\Logger');
        $logger->info(sprintf('Getting list of %s', $this->getPluralName()));

        $paginated = $this->isPaginated();
        $showInactive = $this->isShowInactive();
        $perPage = $this->getPerPage();
        $page = $this->getCurrentPage();
        $order = $this->getOrder();

        $logger->debug(sprintf('Per page %d | page %d | paginated %s | showInactive %s',
            $perPage, $page, var_export($paginated, true), var_export($showInactive, true)));

        $entries = $this->getService()->fetchAll(
            $this->getFilterList(),
            array(
                'page' => $page,
                'paginated' => $paginated,
                'perPage' => $perPage,
                'showInactive' => $showInactive,
                'order' => $order,
            )
        );

        if (!$paginated) {
            return new JsonModel(array(
                $this->normalizeName($this->getPluralName()) => $entries->toArray(),
                'page' => 1,
                'pages' => 1,
                'messages' => [],
            ));
        } else {
            $currentItems = $entries->getCurrentItems();
            if (method_exists($currentItems, 'getArrayCopy')) {
                $currentItems = $currentItems->getArrayCopy();
            }
            return new JsonModel(array(
                $this->normalizeName($this->getPluralName()) => $currentItems,
                'page' => $entries->getCurrentPageNumber(),
                'pages' => $entries->getPages()->pageCount,
                'messages' => [],
            ));
        }
    }

    /**
     * Get an entry
     *
     * @param mixed $id
     * @access public
     * @return JsonModel
     */
    public function get($id) {
        $logger = $this->getServiceLocator()->get('ABSCore\Core\Log\Logger');
        $logger->info(sprintf('Getting %s by id %d', $this->getSingularName(), $id));

        $service = $this->getService();
        $showInactive = $this->params()->fromQuery('show_inactive', false) !== false;
        $messages = [];
        $entry = null;
        try {
            $entry = $service->find($id, ['showInactive' => $showInactive]);
        } catch (Exception\UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'text' => $e->getMessage()];
        } catch (UnknowRegistryException $e) {
            $logger->info(sprintf('Unknow Registry %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(404);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'text' => "$name with identifier '$id' not exists!"];
            $entry = [];
        }
        return new JsonModel([$this->normalizeName($this->getSingularName()) => $entry, 'messages' => $messages]);

    }

    /**
     * Update an entry
     *
     * @param mixed $id
     * @param mixed $data
     * @access public
     * @return JsonModel
     */
    public function update($id, $data)
    {
        $logger = $this->getServiceLocator()->get('ABSCore\Core\Log\Logger');
        $logger->info(sprintf('Updating %s: id %d', $this->getSingularName(), $id));

        $service = $this->getService();
        $result = ['messages' => []];
        $messages = &$result['messages'];
        $name = ucwords($this->getSingularName());
        try {
            $entry = $service->find($id);

            $form = $service->getForm(Service\DataServiceInterface::FORM_EDIT);
            $data['id'] = $id;
            $form->setData((array)$data);
            if ($form->isValid()) {
                $service->save($id, $form->getData());
                $messages[] = ['type' => 'success', 'text' => "$name edited successfully"];
                $this->getResponse()->setStatusCode(200);
                $result['id'] = $id;
            } else {
                $errors = $form->getInputFilter()->getInvalidInput();
                foreach ($errors as $key => $error) {
                    $result['fields'][] = ['name' => $key, 'errors' => array_values($error->getMessages())];
                }
                $logger->debug(sprintf('Fields Errors %s', json_encode($result['fields'])));
                $this->getResponse()->setStatusCode(400);
                $messages[] = ['type' => 'error', 'text' => 'Some fields are invalid'];
            }
        } catch (Exception\UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'text' => $e->getMessage()];
        } catch (UnknowRegistryException $e) {
            $logger->info(sprintf('Unknow Registry %s', $e->getMessage()));
            $this->getResponse()->setStatusCode(404);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'message' => "$name with identifier '$id' not exists!"];
            $entry = [];
        }
        return new JsonModel($result);
    }

    /**
     * create a new entry
     *
     * @param mixed $data
     * @access public
     * @return JsonModel
     */
    public function create($data)
    {
        $logger = $this->getServiceLocator()->get('ABSCore\Core\Log\Logger');
        $logger->info(sprintf('Creating a new %s', $this->getSingularName()));

        $service = $this->getService();

        $result = ['messages' => []];
        $messages = &$result['messages'];

        $form = $service->getForm(Service\DataServiceInterface::FORM_CREATE);

        $form->setData($data);
        try {
            if ($form->isValid()) {
                $result['id'] = $service->save(null, $form->getData());
                $name = ucwords($this->getSingularName());
                $messages[] = ['type' => 'success', 'text' => "$name created successfully"];
                $this->getResponse()->setStatusCode(201);
            } else {
                $this->getResponse()->setStatusCode(400);

                $errors = $form->getInputFilter()->getInvalidInput();
                foreach ($errors as $key => $error) {
                    $result['fields'][] = ['name' => $key, 'errors' => array_values($error->getMessages())];
                }
                $logger->debug(sprintf('Fields Errors %s', json_encode($result['fields'])));
                $messages[] = ['type' => 'error', 'text' => 'Some fields are invalid'];
            }
        } catch (Exception\UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'text' => $e->getMessage()];
        }
        return new JsonModel($result);
    }

    /**
     * delete an entry
     *
     * @param mixed $id
     * @access public
     * @return JsonModel
     */
    public function delete($id)
    {
        $logger = $this->getServiceLocator()->get('ABSCore\Core\Log\Logger');
        $logger->info(sprintf('Removing %s id %d', $this->getSingularName(), $id));
        $service = $this->getService();
        $messages = [];
        $name = ucwords($this->getSingularName());
        try {
            $entry = $service->find($id);
            $service->delete($id);
            $messages[] = ['type' => 'success', 'text' => "$name removed successfully!"];
        } catch (Exception\UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'text' => $e->getMessage()];
        } catch (UnknowRegistryException $e) {
            $logger->info(sprintf('Unknow Registry %s', $e->getMessage()));
            $this->getResponse()->setStatusCode(404);
            $messages[] = ['type' => 'error', 'code' => $e->getCode(), 'text' => "$name with identifier '$id' not exists!"];
            $entry = [];
        }

        return new JsonModel(['messages' => $messages]);
    }

    /**
     * get singular name
     *
     * @access public
     * @return string
     */
    public function getSingularName()
    {
        return $this->singularName;
    }

    /**
     * get plural name
     *
     * @access public
     * @return string
     */
    public function getPluralName()
    {
        return $this->pluralName;
    }

    /**
     * Set identifier names
     *
     * @param string $singularName
     * @param string | null $pluralName
     * @access protected
     * @return RestController
     */
    protected function setNames($singularName, $pluralName = null)
    {
        $this->singularName = strtolower((string)$singularName);
        if (is_null($pluralName)) {
            $pluralName = $singularName.'s';
        }
        $this->pluralName = strtolower($pluralName);
        return $this;
    }

    /**
     * Normalize Name
     *
     * @param string $name
     * @access protected
     * @return string
     */
    protected function normalizeName($name)
    {
        return preg_replace('/ +/', '-', $name);
    }

    /**
     * Method to get filter list
     *
     * @access protected
     * @return mixed
     */
    protected function getFilterList()
    {
        return null;
    }

    /**
     * Get order
     *
     * @access protected
     * @return array
     */
    protected function getOrder()
    {
        $sort = $this->params()->fromQuery('sort');
        $order = [];
        if (!is_null($sort)) {
            $aux = $this->params()->fromQuery('order');
            if (!is_null($aux)) {
                $order[$sort] = $aux;
            } else {
                $order[] = $sort;
            }
        }
        return $order;
    }

    /**
     * Get how many entries per page
     *
     * @access protected
     * @return int
     */
    protected function getPerPage()
    {
        $defaultLimit = $this->getDefaultLimitPerPage();
        $perPage = (int)$this->params()->fromQuery('size', $defaultLimit);
        if ($perPage <= 0) {
            $perPage = $defaultLimit;
        }
        return $perPage;
    }

    /**
     * Current request must show inactive entries
     *
     * @access protected
     * @return bool
     */
    protected function isShowInactive()
    {
        return $this->params()->fromQuery('show_inactive', false) !== false;
    }

    /**
     * Get current page number
     *
     * @access protected
     * @return int
     */
    protected function getCurrentPage()
    {
        return (int)$this->params()->fromQuery('page', 1);
    }
}
