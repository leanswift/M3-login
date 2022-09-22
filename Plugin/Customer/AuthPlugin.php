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
 * @copyright   Copyright (c) 2021 LeanSwift Inc. (http://www.leanswift.com)
 * @license     https://www.leanswift.com/end-user-licensing-agreement
 *
 */

namespace LeanSwift\Login\Plugin\Customer;

use Closure;
use LeanSwift\Login\Helper\AuthClient;
use LeanSwift\Login\Helper\Data;
use LeanSwift\Login\Model\Authentication;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

final class AuthPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepo;
    /**
     * @var Authentication
     */
    protected $authModel;
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
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
     * @param AuthClient $authClient
     * @param SessionManagerInterface $coreSession
     * @param ManagerInterface $manager
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepo
     * @param Authentication $auth
     */
    public function __construct(
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        AuthClient $authClient,
        SessionManagerInterface $coreSession,
        ManagerInterface $manager,
        Data $helper,
        CustomerRepositoryInterface $customerRepo,
        Authentication $auth
    ) {
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $authClient;
        $this->_coreSession = $coreSession;
        $this->messageManager = $manager;
        $this->helper = $helper;
        $this->customerRepo = $customerRepo;
        $this->authModel = $auth;
    }

    /**
     * @param AccountManagement $subject
     * @param Closure $proceed
     * @param $username
     * @param $password
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundAuthenticate(AccountManagement $subject, Closure $proceed, $username, $password)
    {
        $isEnable = $this->auth->isEnable();
        $flag = true;
        $dnsArray =[];
        $domain ="";
        if ($isEnable) {
            if($username)
            {
             $domain = substr($username, strpos($username, '@') + 1);
            }
            $dns = $this->auth->getDomain();
            if($dns)
            {
             $dnsArray = explode(",", $dns);
            }
            if (in_array($domain, $dnsArray)) {
                $this->_coreSession->start();
                $this->_coreSession->setEmail($username);
                $flag = false;
                $redirectionUrl = $this->auth->getOauthLink();
                if ($redirectionUrl) {
                    $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                } else {
                    throw new LocalizedException(__('Authentication Failed'));
                }
            }
        }
        if ($flag) {
            return $proceed($username, $password);
        }
    }

    /**
     * Get authentication code for email trying to login
     *
     * @param $email
     * @return mixed|string
     */
    public function getAuthenticationCode($email)
    {
        try {
            $customerInfo = $this->customerRepo->get($email);
            $attributeInfo = $customerInfo->getCustomAttribute('authentication_code');
            if ($attributeInfo instanceof AttributeInterface) {
                return $attributeInfo->getValue();
            }
        } catch (LocalizedException $e) {
        }
        return '';
    }
}
