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

namespace LeanSwift\Login\Model\Api;

use LeanSwift\EconnectBase\Helper\Constant;
use LeanSwift\EconnectBase\Helper\Data as BaseDataHelper;
use LeanSwift\EconnectBase\Helper\Secure;
use LeanSwift\Login\Helper\AuthClient;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use Magento\Customer\Model\SessionFactory as CustomerSession;

class Adapter extends AbstractModel
{
    /**
     * LeanSwift Helper Authorize
     *
     * @var Secure
     */
    protected $_helperSecure = null;

    /**
     * LeanSwift LOGIN Authorize
     *
     * @var AuthClient
     */
    protected $helperAuth = null;

    /**
     * @var BaseDataHelper
     */
    protected $baseDataHelper;

    /**
     * @var Authentication
     */
    protected $auth;

    /**
     * Magento customer session
     *
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * Adapter constructor.
     * @param Secure $helperSecure
     * @param BaseDataHelper $baseDataHelper
     * @param AuthClient $authClient
     * @param CustomerSession $customerSession
     */
    public function __construct(Secure $helperSecure, BaseDataHelper $baseDataHelper, AuthClient $authClient, CustomerSession $customerSession)
    {
        $this->baseDataHelper = $baseDataHelper;
        $this->_helperSecure = $helperSecure;
        $this->helperAuth = $authClient;
        $this->customerSession = $customerSession;
    }

    public function getCustomerSession()
    {
        return $this->customerSession->create();
    }

    /**
     * @return Zend_Http_Client
     * @throws NoSuchEntityException
     * @throws Zend_Http_Client_Exception
     */
    protected function setAccessToken()
    {
        $client = $this->_helperSecure->getClient();
        $storeId = $this->baseDataHelper->getStoreId();
        $client->setConfig(['timeout' => 60]);
        $customer = $this->getCustomerSession();
        if ($customer->isLoggedIn()) {
            $accessToken = $this->helperAuth->getAccessToken();
        } else {
            //Preparing Access token for oAuth
            $accessToken = $this->_helperSecure->getAccessToken();
            if ($accessToken == '' || $accessToken == null) {
                $this->_helperSecure->createAccessToken($storeId);
                $accessToken = $this->_helperSecure->getAccessToken();
            }
        }
        //Preparing header info for oAuth verification
        $client->setHeaders(
            ['Authorization' => 'Bearer ' . $accessToken]
        );

        return $client;
    }
}
