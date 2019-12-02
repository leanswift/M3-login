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

namespace LeanSwift\Login\Plugin\Customer;

use Closure;
use LeanSwift\Login\Helper\AuthClient;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;


/**
 * Class AuthPlugin
 *
 * @package LeanSwift\Login\Plugin\Customer
 */
final class AuthPlugin
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var AuthClient
     */
    private $auth;

    /**
     * AuthPlugin constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        AuthClient $authClient,
        SessionManagerInterface $coreSession
    ) {
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $authClient;
        $this->_coreSession = $coreSession;
    }

    /**
     * @param AccountManagement $subject
     * @param Closure           $proceed
     * @param                   $username
     * @param                   $password
     *
     * @return mixed
     */
    public function aroundAuthenticate(AccountManagement $subject, Closure $proceed, $username, $password)
    {
        $isEnable = $this->auth->isEnable();
        $flag = true;
        if ($isEnable) {
            $domain = substr($username, strpos($username, '@') + 1);
            $dns = $this->auth->getDomain();
            $dnsArray = explode(",", $dns);
            if (in_array($domain, $dnsArray)) {
                $this->_coreSession->start();
                $this->_coreSession->setEmail($username);
                $flag = false;
                $redirectionUrl = $this->auth->getOauthLink();
                $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
            }
        }
        if ($flag) {
            return $proceed($username, $password);
        }

    }

}
