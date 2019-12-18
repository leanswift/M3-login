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

namespace LeanSwift\Login\Plugin\Customer;

use Closure;
use LeanSwift\Login\Helper\AuthClient;
use LeanSwift\Login\Helper\Data;
use LeanSwift\Login\Model\Authentication;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Setup\Exception;
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
     * AuthPlugin constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
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
                $authCode = $this->getAuthenticationCode($username);
                if(!$authCode) {
                    $this->_coreSession->start();
                    $this->_coreSession->setEmail($username);
                    $flag = false;
                    $redirectionUrl = $this->auth->getOauthLink();
                    if($redirectionUrl)
                    {
                        $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                    }
                    else {
                        throw new LocalizedException(__('Authentication Failed'));
                    }
                }
                //Generate token
//                else {
//                    //$this->authModel->generateToken($authCode);
//                }
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
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAuthenticationCode($email)
    {
        $customerInfo = $this->customerRepo->get($email);
        $attributeInfo = $customerInfo->getCustomAttribute('authentication_code');
        if($attributeInfo instanceof AttributeInterface) {
            return $attributeInfo->getValue();
        }
        return '';
    }

}
