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
 *   @copyright   Copyright (c) 2021 LeanSwift Inc. (http://www.leanswift.com)
 *   @license     https://www.leanswift.com/end-user-licensing-agreement
 *
 */

namespace LeanSwift\Login\Helper;

use LeanSwift\Login\Model\Authentication;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const VERSION_LABEL = 'M3 Login';
    const VERSION = '1.0.0';

    private $authClient;

    private $authModel;

    private $erpApi;

    /**
     * Data constructor.
     * @param Context $context
     * @param Erpapi $erpApi
     * @param Authentication $authentication
     */
    public function __construct(
        Context $context,
        Erpapi $erpApi,
        Authentication $authentication
    ) {
        $this->authModel = $authentication;
        $this->erpApi = $erpApi;
        parent::__construct($context);
    }

    public function authModel()
    {
        return $this->authModel;
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
