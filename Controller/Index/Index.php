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

use Exception;
use LeanSwift\Login\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Index constructor.
     *
     * @param Context        $context
     * @param Data           $helperData
     * @param Authentication $authentication
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepo,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        Session $customerSession,
        Data $data,
        SessionManagerInterface $coreSession
    ) {
        $this->helper = $data;
        $this->customerRepo = $customerRepo;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $info = $this->getRequest()->getParams('code');
        if ($info && array_key_exists('code', $info)) {
            $code = $info['code'];
            $accessToken = $this->helper->authModel()->generateToken($code);
            $userDetails = $this->helper->authModel()->getUserName($accessToken);
            if (array_key_exists('username', $userDetails)) {
                $email = $userDetails['email'];
                try {
                    $this->logincustomer($email);
                } catch (Exception $e) {
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
            $this->customerRepo->save($customerInfo);
            $this->logincustomer($email);
            $userInfo = $this->helper->erpapi()->getUserRoles($username);
            $this->helper->erpapi()->updateuser($username, $userInfo);
        } catch (Exception $e) {
            $this->helper->writeLog($e->getMessage());
        }
        //$customer->sendNewAccountEmail();
    }
}
