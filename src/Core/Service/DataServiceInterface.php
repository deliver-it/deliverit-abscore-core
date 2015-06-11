<?php

namespace ABSCore\Core\Service;


interface DataServiceInterface
{
    const FORM_CREATE = 'create';
    const FORM_EDIT = 'update';
    public function fetchAll($where = null, $params = array());
    public function find($id);
    public function save($id, $data);
    public function delete($id);
    public function getForm($label);
}
