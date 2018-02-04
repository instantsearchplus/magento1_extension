<?php
// Creating API user and role
$role_model = Mage::getModel('api/roles');
$roles = $role_model->getCollection()->getData();
$user_model = Mage::getModel('api/user');
$users = $user_model->getCollection()->getData();

$role_name = 'InstantS';
if (isset($roles)) {
    foreach ($roles as $role) {
        if ($role['role_name'] == $role_name) {
            try {
                $role_model->setId($role['role_id'])->delete();
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }
    }
}

$user_name = 'instant_search';
foreach ($users as $user) {
    if ($user['username'] == $user_name) {
        try {
            $user_model->setId($user['user_id'])->delete();
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }
}

$role_model = Mage::getModel('api/roles');
$user_model = Mage::getModel('api/user');

$role_model->setName($role_name)->setPid(false)->setRoleType('G')->save();

$role_id = $role_model->getId();

Mage::getModel("api/rules")->setRoleId($role_id)->setResources(array('all'))->saveRel();

$user_model->setData(array(
        'username'             => $user_name,
        'firstname'            => 'instant',
        'lastname'             => 'search',
        'email'                => 'owner@example.com',
        'api_key'              => 'Rilb@kped3',
        'api_key_confirmation' => 'Rilb@kped3',
        'is_active'            => 1,
        'user_roles'           => '',
        'assigned_user_role'   => '',
        'role_name'            => '',
        'roles'                => array($role_id)
    ))->save();

$user_model->setRoleIds(array($role_id))->setRoleUserId($user_model->getUserId())->saveRelations();