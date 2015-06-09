<?php

namespace ABSCore\Core\Service;


interface DataServiceInterface
{
    public function fetchAll($where = null, $params = array());
    public function find($id);
    public function save($id, $data);
    public function delete($id);
    public function getForm($label);
}
