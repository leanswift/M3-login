<?php
/**
 * LeanSwift eConnect Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the LeanSwift eConnect Extension License
 * that is bundled with this package in the file LICENSE.txt located in the
 * Connector Server.
 *
 * DISCLAIMER
 *
 * This extension is licensed and distributed by LeanSwift. Do not edit or add
 * to this file if you wish to upgrade Extension and Connector to newer
 * versions in the future. If you wish to customize Extension for your needs
 * please contact LeanSwift for more information. You may not reverse engineer,
 * decompile, or disassemble LeanSwift Connector Extension (All Versions),
 * except and only to the extent that such activity is expressly permitted by
 * applicable law not withstanding this limitation.
 *
 * @copyright   Copyright (c) 2019 LeanSwift Inc. (http://www.leanswift.com)
 * @license     https://www.leanswift.com/end-user-licensing-agreement
 */

namespace LeanSwift\Login\Model\ResourceModel;

use Exception;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Userrole
 *
 * @package LeanSwift\Login\Model\ResourceModel
 */
class Userrole extends AbstractDb
{

    /**
     * Initialize the object
     */
    public function _construct()
    {
        $this->_init('leanswift_login_user', 'id');
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function updateRoles($data)
    {
        $tableName = $this->getTable('leanswift_login_roles');
        $this->truncate($tableName);
        return $this->updateRecord($tableName, $data);
    }

    private function truncate($tableName)
    {
        $adapter = $this->getConnection();
        $adapter->truncateTable($tableName);
    }

    public function updateRecord($tableName, $data, $fields = [])
    {
        try {
            $adapter = $this->getConnection();
            $adapter->insertOnDuplicate($tableName, $data, $fields);
            $flag = true;
        } catch (Exception $e) {
            $flag = false;
        }

        return $flag;
    }

    public function updateUser($data)
    {
        $tableName = $this->getTable('leanswift_login_user');
        $username = $data['username'];
        $this->deleteRecord($username);
        return $this->updateRecord($tableName, $data);
    }

    public function deleteRecord($username)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $table = $this->getMainTable();
            $where = ['username = ?' => $username];
            $connection->delete($table, $where);
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
        }
    }

    public function updateRoleInfo($data)
    {
        $tableName = $this->getTable('leanswift_login_roleinfo');
        $this->truncate($tableName);
        return $this->updateRecord($tableName, $data);
    }

    public function getRolesByUser($username)
    {
        $adapter = $this->getConnection();
        $tableName = $this->getTable('leanswift_login_user');
        $select = $adapter->select()->from($tableName)
            ->where('username=:username');

        $binds = ['username' => $username];

        return $adapter->fetchRow($select, $binds);
    }

    public function getRolesList()
    {
        $adapter = $this->getConnection();
        $tableName = $this->getTable('leanswift_login_roles');
        $select = $adapter->select()->from($tableName);

        return $adapter->fetchRow($select);
    }

    public function getFuncByRoles($role, $cono = false, $divi = false)
    {
        $adapter = $this->getConnection();
        $tableName = $this->getTable('leanswift_login_roleinfo');
        $select = $adapter->select()->from($tableName)
            ->where("role=$role");
        if ($cono) {
            $adapter->where("company=$cono");
        }
        if ($divi) {
            $adapter->where("division=$divi");
        }

        return $adapter->fetchRow($select);
    }
}
