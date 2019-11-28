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

use LeanSwift\Econnect\Helper\Data;
use LeanSwift\Econnect\Helper\Secure;
use Magento\Config\Model\ResourceModel\Config as systemConfigValue;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Session\SessionManager;
use LeanSwift\Login\Model\Authentication;

class AuthClient extends Secure
{

    const XML_PATH_WEB_MINGLE_URL = 'leanswift_login/general/mingle_url';

    const XML_PATH_WEB_SERVICE_URL = 'leanswift_login/authentication/service_url';

    const XML_PATH_ION_URL = 'ion/general_config/service_url';

    const XML_PATH_WEB_SERVICE_CLIENTID = 'leanswift_login/authentication/web_service_clientid';

    const XML_PATH_WEB_SERVICE_CLIENTSECRET = 'leanswift_login/authentication/web_service_clientsecret';

    const XML_PATH_DOMAIN = 'leanswift_login/general/domain_name';

    const XML_PATH_ENABLE = 'leanswift_login/general/enable_login';

    /**
     * @var Authentication
     */
    protected $auth;

    public function __construct(
        Context $context,
        ZendClient $zendClient,
        Curl $adapterCurl,
        EncryptorInterface $encryptor,
        systemConfigValue $saveSystemConfig,
        SessionManager $sessionStorage,
        TypeListInterface $cacheTypeList,
        ResourceConnection $resourceConnection,
        Data $helperData,
        Authentication $authentication
    ) {
        $this->auth = $authentication;
        parent::__construct($context, $zendClient, $adapterCurl, $encryptor, $saveSystemConfig, $sessionStorage,
            $cacheTypeList, $resourceConnection, $helperData);
    }


    /**
     * Get Login API Client Id
     *
     * @param null $storeId
     *
     * @return mixed|string
     */
    public function getClientId($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_WEB_SERVICE_CLIENTID,
            $this->_dataHelper->getStoreScope(),
            $storeId);
    }

    /**
     * Get Login API Client Secret
     *
     * @param null $storeId
     *
     * @return string
     */
    public function getClientSecret($storeId = null)
    {
        return $this->_encryptorInterface->decrypt($this->scopeConfig->getValue(
            self::XML_PATH_WEB_SERVICE_CLIENTSECRET,
            $this->_dataHelper->getStoreScope(),
            $storeId
        ));
    }

    public function getOauthLink()
    {
        $link = $this->scopeConfig->getValue(self::XML_PATH_WEB_SERVICE_URL);
        $clientId = $this->getClientId();
        $param = "as/authorization.oauth2?client_id=$clientId&response_type=code";
        $oauthLink = $link . $param;
        return $oauthLink;
    }

    public function getTokenLink()
    {
        $link = $this->scopeConfig->getValue(self::XML_PATH_WEB_SERVICE_URL);
        $oauthLink = $link . 'as/token.oauth2';
        return $oauthLink;
    }

    public function getMingleLink()
    {
        $link = $this->scopeConfig->getValue(self::XML_PATH_WEB_MINGLE_URL);
        return $link;
    }

    public function getIonLink()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ION_URL);
    }

    public function logger()
    {
        return $this->_dataHelper;
    }

    public function getDomain()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DOMAIN);
    }

    public function isEnable()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE);
    }

    public function getAccessToken($storeId = null)
    {
        return $this->_session->getAccessToken();
    }

    public function createAccessToken($storeId = null)
    {
        return $this->auth->requestToken();
    }

}