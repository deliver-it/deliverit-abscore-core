<?php

namespace ABSCore\Core\Service;

interface PermissionsInterface
{
    public function isAllowed($identifier, $method, array $params = []);
}
