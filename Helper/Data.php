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

use LeanSwift\Login\Model\Authentication;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Monolog\Logger;

class Data extends AbstractHelper
{
    const VERSION_LABEL = 'M3 LOGIN';
    const VERSION = '1.0.0';

    private $authClient;

    private $authModel;

    private $erpApi;
    /**
     * @var Logger
     */
    protected $logger;
    protected $isLogEnabled;

    public function __construct(
        Context $context,
        Erpapi $erpapi,
        Authentication $authentication,
        AuthClient $authClient,
        Logger $logger
    ) {
        $this->authModel = $authentication;
        $this->authClient = $authClient;
        $this->erpApi = $erpapi;
        $this->logger = $logger;
        parent::__construct($context);
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

    /**
     * @param $message
     */
    public function writeLogInfo($message)
    {
        if($this->isLogEnabled == '')
        {
            $this->isLogEnabled = $this->scopeConfig->getValue(Constant::LOGGER_ENABLE_PATH);
        }
        if($this->isLogEnabled == 1)
        {
            $this->logger->info($message);
        }
    }
}
