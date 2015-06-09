<?php

namespace ABSCore\Core\Controller\API;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

use ABSCore\Core\Exception\UnauthorizedException;

use ABSCore\DataAccess\Exception\UnknowRegistryException;

class RestController extends AbstractRestfulController
{

    public function __construct($service, $singularName, $pluralName = null)
    {
        $this->setService($service);
        $this->setNames($singularName, $pluralName);
    }

    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    public function getService()
    {
        return $this->service;
    }

    public function getList()
    {
        $logger = $this->getServiceLocator()->get('Logger');
        $logger->info(sprintf('Getting list of %s', $this->getPluralName()));
        $perPage = (int)$this->params()->fromQuery('size', 100);
        if ($perPage <= 0) {
            $perPage = 100;
        }

        $sort = $this->params()->fromQuery('sort');
        $order = array();
        if (!is_null($sort)) {
            $aux = $this->params()->fromQuery('order');
            if (!is_null($aux)) {
                $order[$sort] = $aux;
            } else {
                $order[] = $sort;
            }
        }

        $paginated = ($this->params()->fromQuery('show_all', false) === false);

        $showInactive = $this->params()->fromQuery('show_inactive', false) !== false;

        $page = (int)$this->params()->fromQuery('page', 1);

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
                'messages' => array(),
            ));
        } else {
            return new JsonModel(array(
                $this->normalizeName($this->getPluralName()) => $entries->getCurrentItems()->toArray(),
                'page' => $entries->getCurrentPageNumber(),
                'pages' => $entries->getPages()->pageCount,
                'messages' => array(),
            ));
        }
    }

    public function get($id) {
        $logger = $this->getServiceLocator()->get('Logger');
        $logger->info(sprintf('Getting %s by id %d', $this->getSingularName(), $id));

        $service = $this->getService();
        $showInactive = $this->params()->fromQuery('show_inactive', false) !== false;
        $messages = array();
        $entry = null;
        try {
            $entry = $service->find($id, array('showInactive' => $showInactive));
        } catch (UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = array('type' => 'error', 'code' => 'ERROR', 'text' => $e->getMessage());
        } catch (UnknowRegistryException $e) {
            $logger->info(sprintf('Unknow Registry %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(404);
            $messages[] = array('type' => 'error', 'code' => 'ERROR001', 'text' => "$name with identifier '$id' not exists!");
            $entry = array();
        }
        return new JsonModel(array($this->normalizeName($this->getSingularName()) => $entry, 'messages' => $messages));

    }

    public function update($id, $data)
    {
        $logger = $this->getServiceLocator()->get('Logger');
        $logger->info(sprintf('Updating %s: id %d', $this->getSingularName(), $id));

        $service = $this->getService();
        $result = array('messages' => array());
        $messages = &$result['messages'];
        $name = ucwords($this->getSingularName());
        try {
            $entry = $service->find($id);

            $form = $service->getForm('edit');
            $data['id'] = $id;
            $form->setData((array)$data);
            if ($form->isValid()) {
                $service->save($id, $form->getData());
                $messages[] = array('type' => 'success', 'text' => "$name edited successfully");
                $this->getResponse()->setStatusCode(200);
                $result['id'] = $id;
            } else {
                $errors = $form->getInputFilter()->getInvalidInput();
                foreach ($errors as $key => $error) {
                    $result['fields'][] = array('name' => $key, 'errors' => array_values($error->getMessages()));
                }
                $logger->debug(sprintf('Fields Errors %s', json_encode($result['fields'])));
                $this->getResponse()->setStatusCode(400);
                $messages[] = array('type' => 'error', 'text' => 'Some fields are invalid');
            }
        } catch (UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = array('type' => 'error', 'code' => 'ERROR', 'text' => $e->getMessage());
        } catch (UnknowRegistryException $e) {
            $logger->info(sprintf('Unknow Registry %s', $e->getMessage()));
            $this->getResponse()->setStatusCode(404);
            $messages[] = array('type' => 'error', 'code' => 'ERROR001', 'message' => "$name with identifier '$id' not exists!");
            $entry = array();
        }
        return new JsonModel($result);
    }

    public function create($data)
    {
        $logger = $this->getServiceLocator()->get('Logger');
        $logger->info(sprintf('Creating a new %s', $this->getSingularName()));

        $service = $this->getService();

        $result = array('messages' => array());
        $messages = &$result['messages'];

        $form = $service->getForm();

        $form->setData($data);
        try {
            if ($form->isValid()) {
                $result['id'] = $service->save(null, $form->getData());
                $name = ucwords($this->getSingularName());
                $messages[] = array('type' => 'success', 'text' => "$name created successfully");
                $this->getResponse()->setStatusCode(201);
            } else {
                $this->getResponse()->setStatusCode(400);

                $errors = $form->getInputFilter()->getInvalidInput();
                foreach ($errors as $key => $error) {
                    $result['fields'][] = array('name' => $key, 'errors' => array_values($error->getMessages()));
                }
                $logger->debug(sprintf('Fields Errors %s', json_encode($result['fields'])));
                $messages[] = array('type' => 'error', 'text' => 'Some fields are invalid');
            }
        } catch (UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = array('type' => 'error', 'code' => 'ERROR', 'text' => $e->getMessage());
        }
        return new JsonModel($result);
    }

    public function delete($id)
    {
        $logger = $this->getServiceLocator()->get('Logger');
        $logger->info(sprintf('Removing %s id %d', $this->getSingularName(), $id));
        $service = $this->getService();
        $messages = array();
        $name = ucwords($this->getSingularName());
        try {
            $entry = $service->find($id);
            $service->delete($id);
            $messages[] = array('type' => 'success', 'text' => "$name removed successfully!");
        } catch (UnauthorizedException $e) {
            $logger->info(sprintf('Unauthorized %s', $e->getMessage()));
            $name = ucwords($this->getSingularName());
            $this->getResponse()->setStatusCode(403);
            $messages[] = array('type' => 'error', 'code' => 'ERROR', 'text' => $e->getMessage());
        } catch (UnknowRegistryException $e) {
            $logger->info(sprintf('Unknow Registry %s', $e->getMessage()));
            $this->getResponse()->setStatusCode(404);
            $messages[] = array('type' => 'error', 'code' => 'ERROR001', 'text' => "$name with identifier '$id' not exists!");
            $entry = array();
        }

        return new JsonModel(array('messages' => $messages));
    }

    public function getSingularName()
    {
        return $this->singularName;
    }

    public function getPluralName()
    {
        return $this->pluralName;
    }

    protected function setNames($singularName, $pluralName = null)
    {
        $this->singularName = strtolower((string)$singularName);
        if (is_null($pluralName)) {
            $pluralName = $singularName.'s';
        }
        $this->pluralName = strtolower($pluralName);
    }

    protected function normalizeName($name)
    {
        return preg_replace('/ +/', '-', $name);
    }

    protected function getFilterList()
    {
        return null;
    }
}
