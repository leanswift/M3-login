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
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use LeanSwift\Login\Model\Authentication;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Index extends Action
{

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Authentication
     */
    protected $authModel;

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
        CustomerFactory $customerFactory,
        Session $customerSession,
        SessionManagerInterface $coreSession
    ) {
        $this->_helper = $helperData;
        $this->authModel = $authentication;
        $this->customerRepo = $customerRepo;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->_coreSession = $coreSession;

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
            if ($accessToken) {
                $email =  $this->_coreSession->getEmail();
                try{
                    $customerRepo = $this->customerRepo->get($email);                    //load with email
                    $customer = $this->customerFactory->create()->load($customerRepo->getId());     //get the customer model by id
                    $this->customerSession->setCustomerAsLoggedIn($customer);
                } catch (\Exception $e)
                {
                    $this->messageManager->addNoticeMessage('Email is not registered!');
                }
            }
        }

        $this->_redirect(self::PATH);
    }
}
