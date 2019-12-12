<?php
/**
 *  LeanSwift Login Extension
 *
 *  DISCLAIMER
 *
 *   This extension is licensed and distributed by LeanSwift. Do not edit or add
 *   to this file if you wish to upgrade Extension and Connector to newer
 *   versions in the future. If you wish to customize Extension for your needs
 *   please contact LeanSwift for more information. You may not reverse engineer,
 *   decompile, or disassemble LeanSwift Login Extension (All Versions),
 *   except and only to the extent that such activity is expressly permitted by
 *    applicable law not withstanding this limitation.
 *
 *   @copyright   Copyright (c) 2019 LeanSwift Inc. (http://www.leanswift.com)
 *   @license     https://www.leanswift.com/end-user-licensing-agreement
 *
 */

namespace LeanSwift\Login\Helper;

use LeanSwift\Econnect\Helper\Ion as IonHelper;
use LeanSwift\Login\Model\Api\Adapter;
use LeanSwift\Login\Model\ResourceModel\Userrole;

/**
 * Class Erpapi
 *
 * @package LeanSwift\Login\Helper
 */
class Erpapi
{

    /**
     * Files for Initial Load - eConnect Add-on
     */
    const ROLE_BY_USER = '/apiTxn/MNS410MI/LstRolesByUser';

    const ROLE_LIST = '/apiTxn/MNS405MI/Lst';

    const ROLE_INFO = '/apiTxn/SES400MI/Lst';

    const AUTH_BY_ROLE = '/apiTxn/SES400MI/LstAuthByRole';

    /**
     * @var Adapter
     */
    private $apiadapter;

    /**
     * @var IonHelper
     */
    private $helper;

    /**
     * @var Userrole
     */
    private $userrole;

    /**
     * Erpapi constructor.
     *
     * @param Adapter   $adapter
     * @param IonHelper $ion
     * @param Userrole  $userroleResource
     */
    public function __construct(Adapter $adapter, IonHelper $ion, Userrole $userroleResource)
    {
        $this->apiadapter = $adapter;
        $this->helper = $ion;
        $this->userrole = $userroleResource;
    }

    public function getUserRoles($username)
    {
        $method = self::ROLE_BY_USER;
        $requestData['USID'] = $username;
        $response = $this->doRequest($method, $requestData, 60);
        $response = json_decode($response, true);
        $roles = [];
        if (is_array($response) && array_key_exists('output', $response)) {
            foreach ($response['output'] as $data) {
                $res['Role'] = $data['ROLL'];
                $res['ValidFrom'] = ($data['FVDT']) ?? '';
                $res['ValidTo'] = ($data['VTDT']) ?? '';
                $roles [] = $res;
                unset($res);
            }
        }

        return $this->helper->getSerializeObject()->serialize($roles);
    }

    public function doRequest($method, $requestData, $timeout = 30)
    {
        return $this->apiadapter->_sendRequest($method, $requestData, $timeout);
    }

    public function getRolesList()
    {
        $method = self::ROLE_LIST;
        $requestData['ROLL'] = '';
        $response = $this->doRequest($method, $requestData, 60);
        $response = json_decode($response, true);
        $roleList = [];
        if (is_array($response) && array_key_exists('output', $response)) {
            foreach ($response['output'] as $data) {
                $res['role'] = $data['ROLL'];
                $res['name'] = $data['TX15'];
                $res['description'] = ($data['TX40']) ?? '';
                $roleList [] = $res;
                unset($res);
            }
        }

        return $roleList;
    }

    public function getRolesInfo()
    {
        $method = self::ROLE_INFO;
        $requestData['ROLL'] = '';
        $response = $this->doRequest($method, $requestData, 60);
        $response = json_decode($response, true);
        $roleInfo = [];
        if (is_array($response) && array_key_exists('output', $response)) {
            foreach ($response['output'] as $data) {
                $res['role'] = $data['ROLL'];
                $res['function'] = $data['FNID'];
                $res['company'] = ($data['CONO']) ?? '';
                $res['division'] = ($data['DIVI']) ?? '';
                $roleInfo [] = $res;
                unset($res);
            }
        }

        return $roleInfo;
    }

    public function getAuthByRole($role = '', $cono = false, $divi = false)
    {
        $method = self::AUTH_BY_ROLE;
        $requestData['ROLL'] = $role;
        $response = $this->doRequest($method, $requestData, 60);
        $response = json_decode($response, true);
        $roleInfo = [];
        if (is_array($response) && array_key_exists('output', $response)) {
            foreach ($response['output'] as $data) {
                $company = ($data['CONO']) ?? '';
                $division = ($data['DIVI']) ?? '';
                if ($cono == $company && $divi == $division) {
                    $res['role'] = $data['ROLL'];
                    $res['function'] = $data['FNID'];
                    $res['company']
                        = $res['division'] = ($data['DIVI']) ?? '';
                    $roleInfo [] = $res;
                    unset($res);
                }
            }
        }

        return $roleInfo;
    }

    public function updateuser($username, $data)
    {
        $rpwData['username'] = $username;
        $rpwData['roleinfo'] = $data;
        return $this->userrole->updateUser($rpwData);
    }

    public function userRoleModel()
    {
        return $this->userrole;
    }
}
