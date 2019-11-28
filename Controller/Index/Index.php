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

namespace LeanSwift\Login\Controller\Index;

use LeanSwift\Econnect\Helper\Data;
use LeanSwift\Login\Helper\Erpapi;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use LeanSwift\Login\Model\Authentication;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Authentication
     */
    protected $authModel;

    /**
     * @var Erpapi
     */
    protected $apihelper;

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

    const PATH = 'customer/account/login';

    /**
     * Index constructor.
     *
     * @param Context        $context
     * @param Data           $helperData
     * @param Authentication $authentication
     */
    public function __construct(
        Context $context,
        Data $helperData,
        Authentication $authentication,
        CustomerRepositoryInterface $customerRepo,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        Session $customerSession,
        Erpapi $erpapi,
        SessionManagerInterface $coreSession
    ) {
        $this->_helper = $helperData;
        $this->authModel = $authentication;
        $this->customerRepo = $customerRepo;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->storeManager = $storeManager;
        $this->apihelper = $erpapi;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $info = $this->getRequest()->getParams('code');
        if ($info) {
            $code = $info['code'];
            $accessToken = $this->authModel->generateToken($code);
            $userDetails = $this->authModel->getUserName($accessToken);
            if (array_key_exists('username', $userDetails)) {
                $email =  $userDetails['email'];
                try{
                    $this->logincustomer($email);
                } catch (\Exception $e)
                {
                    $this->createCustomer($userDetails);
                }
            }
        }

        $this->_redirect(self::PATH);
    }

    public function logincustomer($email)
    {
        $customerRepo = $this->customerRepo->get($email);                    //load with email
        $customer = $this->customerFactory->create()->load($customerRepo->getId());     //get the customer model by id
        $this->customerSession->setCustomerAsLoggedIn($customer);

    }

    public function createCustomer($userDetailList)
    {
            $email = $userDetailList['email'];
            $firstName = $userDetailList['firstname'];
            $lastName = $userDetailList['lastname'];
            $username = $userDetailList['username'];
            // Get Website ID
            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

            // Instantiate object (this is the most important part)
            $customer   = $this->customerFactory->create();
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
                $this->customerRepo->save($customerInfo);
                $this->logincustomer($email);
                $userInfo = $this->apihelper->getUserRoles($username);
                $this->apihelper->updateuser($username, $userInfo);
            } catch (\Exception $e) {
                $this->auth->logger()->writeLog($e->getMessage());
            }
            //$customer->sendNewAccountEmail();
        }
}
