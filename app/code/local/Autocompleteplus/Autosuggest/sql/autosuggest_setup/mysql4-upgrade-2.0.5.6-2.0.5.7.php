<?php

$rolesModel = Mage::getModel('api/roles');
$userModel = Mage::getModel('api/user');
$apiRoles = $rolesModel->getCollection();
$apiUsers = $userModel->getCollection();
$roleName = 'InstantS';
$userName = 'instant_search';

$apiRoles->addFieldToFilter('role_name', $roleName);
$apiUsers->addFieldToFilter('username', $userName);

$apiRoles->getSelect()->limit(1);
$apiUsers->getSelect()->limit(1);

if ($apiRoles->getSize() > 0) {
    // @codingStandardsIgnoreLine
    $apiRoles->getFirstItem()->delete();
}

if ($apiUsers->getSize() > 0) {
    // @codingStandardsIgnoreLine
    $apiUsers->getFirstItem()->delete();
}

$rolesModel->setName($roleName)->setPid(false)->setRoleType('G')->save();

$roleId = $rolesModel->getId();

Mage::getModel('api/rules')->setRoleId($roleId)->setResources(array('all'))->saveRel();

$userModel->setData(array(
        'username' => $userName,
        'firstname' => 'instant',
        'lastname' => 'search',
        'email' => 'owner@example.com',
        'api_key' => 'Rilb@kped3',
        'api_key_confirmation' => 'Rilb@kped3',
        'is_active' => 1,
        'user_roles' => '',
        'assigned_user_role' => '',
        'role_name' => '',
        'roles' => array($roleId),
    ))->save();

$userModel->setRoleIds(array($roleId))->setRoleUserId($userModel->getUserId())->saveRelations();

Mage::log(__FILE__ . ' triggered', null, 'autocomplete.log', true);
