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

namespace LeanSwift\Login\Controller\Index;

use Exception;
use LeanSwift\Login\Helper\Data;
use LeanSwift\Login\Helper\Logger;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;


class Index extends Action
{
    const PATH = 'customer/account/login';

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepo;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var string
     */
    protected $redirectPath;
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var array|string
     */
    private $userDetails;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepo
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param Data $data
     * @param SessionManagerInterface $coreSession
     * @param Logger $logger
     * @param string $redirectPath
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepo,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        Session $customerSession,
        Data $data,
        SessionManagerInterface $coreSession,
        Logger $logger,
        $redirectPath = self::PATH
    ) {
        $this->helper = $data;
        $this->customerRepo = $customerRepo;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->redirectPath = $redirectPath;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function execute()
    {
        $code = '';
        $info = $this->getRequest()->getParams();
        try {
            if ($info && array_key_exists('code', $info)) {
                $code = $info['code'];
                if (!$code) {
                    throw new Exception('Authentication code is not present');
                }
                $accessToken = $this->helper->authModel()->generateToken($code);
                if ($accessToken) {
                    $this->loginAsCustomer($accessToken);
                } else {
                    throw new Exception('Access token could not be created');
                }
            } else {
                throw new Exception('Authentication failed from M3');
            }
        } catch (Exception $e) {
            $this->logger->writeLog($e->getMessage());
            $this->messageManager->addErrorMessage('Authentication failed');
        }
        $this->addAuthenticationCode($code);
        $this->_redirect($this->getRedirectPath());
    }

    public function loginAsCustomer($accessToken)
    {
        $userDetails = $this->helper->authModel()->getUserName($accessToken);
        if (!empty($userDetails)) {
            if (array_key_exists('username', $userDetails) && array_key_exists('email', $userDetails)) {
                if (!$userDetails['email']) {
                    throw new Exception('Email is not configured in M3');
                }
                if (!$this->validateEmail($userDetails['email'])) {
                    throw new Exception('Email entered is different from the M3 email');
                }
                $this->userDetails = $userDetails;
                try {
                    try {
                        $this->loginCustomer($userDetails['email']);
                    } catch (Exception $e) {
                        $this->createCustomer($userDetails);
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            } else {
                throw new Exception('Username/Email detail are not present');
            }
        } else {
            throw new Exception('Service URL for Authorization is not configured');
        }
    }

    /**
     * Validate the email with entered email from magento
     *
     * @param $email
     * @return bool
     */
    public function validateEmail($email)
    {
        $loginCustomerEmail = $this->_coreSession->getEmail();
        return (strcasecmp($loginCustomerEmail, $email) == 0);
    }

    public function loginCustomer($email)
    {
        //load with email
        $customerRepo = $this->customerRepo->get($email, $this->storeManager->getWebsite()->getId());
        if ($customerRepo->getId()) {
            $this->customerSession->loginById($customerRepo->getId());
            try {
                $this->_eventManager->dispatch('m3_login_userdetails', ['user_details' => $this->userDetails]);
            }catch (Exception $e) {
                $this->customerSession->logout();
                $this->logger->writeLog($e->getMessage());
                $this->messageManager->addErrorMessage('Authentication failed');
            }
        }
    }

    public function createCustomer($userDetailList)
    {
        $email = $userDetailList['email'];
        $firstName = $userDetailList['firstname'];
        $lastName = $userDetailList['lastname'];
        $username = $userDetailList['username'];
        // Get Website ID
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();

        // Instantiate object (this is the most important part)
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);


        // Preparing data for new customer
        $customer->setEmail($email);
        $customer->setFirstname($firstName);
        $customer->setLastname($lastName);
        $customer->setPassword("password");
        // Save data
        try {
            $customer->save();
            $customerId = $customer->getId();
            $customerInfo = $this->customerRepo->getById($customerId);
            $customerInfo->setCustomAttribute('username', $username);

            $this->_eventManager->dispatch('customer_register_success', [
                    'account_controller' => $this,
                    'customer' => $customerInfo
                ]);
            $this->customerRepo->save($customerInfo);
            $this->loginCustomer($email);
        } catch (Exception $e) {
            $this->logger->writeLog($e->getMessage());
        }
        //$customer->sendNewAccountEmail();
    }

    /**
     * Add authentication code to logged in customer
     *
     * @param $authCode
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function addAuthenticationCode($authCode)
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerInfo = $this->customerSession->getCustomerData()
                ->setCustomAttribute('authentication_code', $authCode);
            $this->customerRepo->save($customerInfo);
        }
    }

    /**
     * @return string
     */
    public function getRedirectPath()
    {
        return $this->redirectPath;
    }
}
