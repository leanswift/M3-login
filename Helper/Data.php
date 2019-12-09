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

use LeanSwift\Econnect\Helper\Ion;
use LeanSwift\Login\Model\Authentication;

class Data extends Ion
{
    const VERSION_LABEL = 'M3 LOGIN';
    const VERSION = '1.0.0';

    private $authClient;

    private $authModel;

    private $erpApi;

    public function __construct(
        Erpapi $erpapi,
        Authentication $authentication,
        AuthClient $authClient
    ) {
        $this->authModel = $authentication;
        $this->authClient = $authClient;
        $this->erpApi = $erpapi;
    }

    public function authModel()
    {
        return $this->authModel;
    }

    public function authClient()
    {
        return $this->authClient;
    }

    public function getRolesList()
    {
        return $this->erpapi()->userRoleModel()->getRolesList();
    }

    public function erpapi()
    {
        return $this->erpApi;
    }

    public function getFuncByRoles($role, $cono = false, $divi = false)
    {
        return $this->erpapi()->userRoleModel()->getFuncByRoles($role, $cono, $divi);
    }

    public function getRolesByUser($username)
    {
        return $this->erpapi()->userRoleModel()->getRolesByUser($username);
    }
}
