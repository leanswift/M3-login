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

namespace LeanSwift\Login\Helper;

use LeanSwift\EconnectBase\Helper\Constant;
use LeanSwift\EconnectBase\Helper\Erpapi as BaseErpApi;
use LeanSwift\Login\Model\ResourceModel\Userrole;
use Magento\Framework\Serialize\SerializerInterface as Json;

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
    protected $baseErpApi;
    protected $serialize;
    /**
     * @var Userrole
     */
    private $userrole;

    /**
     * Erpapi constructor.
     * @param Json $serialize
     * @param Userrole $userroleResource
     * @param BaseErpApi $baseErpApi
     */
    public function __construct(
        Json $serialize,
        Userrole $userroleResource,
        BaseErpApi $baseErpApi
    ) {
        $this->serialize = $serialize;
        $this->userrole = $userroleResource;
        $this->baseErpApi = $baseErpApi;
    }

    public function getSerializerObject()
    {
        return $this->serialize;
    }

    public function getUserRoles($username)
    {
        $method = self::ROLE_BY_USER;
        $requestData['USID'] = $username;
        $response = $this->doRequest($method, $requestData);
        //$response = json_decode($response, true);
        $rolesData = [];

        if ($response && array_key_exists(Constant::DATA, $response)) {
            $responseData = (array_key_exists(Constant::OUTPUT, $response[Constant::DATA])) ? ($response[Constant::DATA]['output']) : false;
            if ($responseData) {
                foreach ($responseData as $data) {
                    $res['Role'] = $data['ROLL'];
                    $res['ValidFrom'] = ($data['FVDT']) ?? '';
                    $res['ValidTo'] = ($data['VTDT']) ?? '';
                    $roles [] = $res;
                    $rolesData = $this->serialize->serialize($roles);
                    unset($res);
                }
            }
        }
        return $rolesData;
    }

    public function doRequest($method, $requestData)
    {
        return $this->baseErpApi->doRequest($requestData, $method);
    }

    public function getRolesList()
    {
        $method = self::ROLE_LIST;
        $requestData['ROLL'] = '';
        $response = $this->doRequest($method, $requestData);
        //$response = json_decode($response, true);
        $roleList = [];

        if ($response && array_key_exists(Constant::DATA, $response)) {
            $responseData = (array_key_exists(Constant::OUTPUT, $response[Constant::DATA])) ? ($response[Constant::DATA]['output']) : false;
            if ($responseData) {
                foreach ($responseData as $data) {
                    $res['role'] = $data['ROLL'];
                    $res['name'] = $data['TX15'];
                    $res['description'] = ($data['TX40']) ?? '';
                    $roleList [] = $res;
                    unset($res);
                }
            }
        }
        return $roleList;
    }

    public function getRolesInfo()
    {
        $method = self::ROLE_INFO;
        $requestData['ROLL'] = '';
        $response = $this->doRequest($method, $requestData);
        //$response = json_decode($response, true);
        $roleInfo = [];

        if ($response && array_key_exists(Constant::DATA, $response)) {
            $responseData = (array_key_exists(Constant::OUTPUT, $response[Constant::DATA])) ? ($response[Constant::DATA]['output']) : false;
            if ($responseData) {
                foreach ($responseData as $data) {
                    $res['role'] = $data['ROLL'];
                    $res['function'] = $data['FNID'];
                    $res['company'] = ($data['CONO']) ?? '';
                    $res['division'] = ($data['DIVI']) ?? '';
                    $roleInfo [] = $res;
                    unset($res);
                }
            }
        }

        return $roleInfo;
    }

    public function getAuthByRole($role = '', $cono = false, $divi = false)
    {
        $method = self::AUTH_BY_ROLE;
        $requestData['ROLL'] = $role;
        $response = $this->doRequest($method, $requestData);
        //$response = json_decode($response, true);
        $roleInfo = [];

        if ($response && array_key_exists(Constant::DATA, $response)) {
            $responseData = (array_key_exists(Constant::OUTPUT, $response[Constant::DATA])) ? ($response[Constant::DATA]['output']) : false;
            if ($responseData) {
                foreach ($responseData as $data) {
                    $company = ($data['CONO']) ?? '';
                    $division = ($data['DIVI']) ?? '';
                    if ($cono == $company && $divi == $division) {
                        $res['role'] = $data['ROLL'];
                        $res['function'] = $data['FNID'];
                        $res['company'] = $res['division'] = ($data['DIVI']) ?? '';
                        $roleInfo [] = $res;
                        unset($res);
                    }
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

    /**
     * Format the XML
     *
     * @param $string
     * @return string|string[]|null
     */
    public function utf8_for_xml($string)
    {
        $string = preg_replace('/[[:^print:]]/', '', $string);

        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    }
}
