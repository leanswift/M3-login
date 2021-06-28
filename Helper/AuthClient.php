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

namespace LeanSwift\Login\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Zend_Http_Client;
use Zend_Http_Client_Adapter_Exception;
use Zend_Http_Client_Adapter_Socket;
use Zend_Http_Client_Exception;
use Magento\Store\Model\StoreManagerInterface;

class AuthClient extends AbstractHelper
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptorInterface;
    /**
     * @var \LeanSwift\EconnectBase\Helper\Data
     */
    protected $baseDataHelper;
    /**
     * @var SessionManagerInterface
     */
    protected $session;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var FormKey
     */
    protected $formKey;
    protected $storeManager;

    public function __construct(
        Context $context,
        EncryptorInterface $encrypt,
        \LeanSwift\EconnectBase\Helper\Data $baseDataHelper,
        SessionManagerInterface $coreSession,
        ManagerInterface $manager,
        Logger $logger,
        FormKey $formKey,
        StoreManagerInterface $storeManager
    ) {
        $this->encryptorInterface = $encrypt;
        $this->baseDataHelper = $baseDataHelper;
        $this->session = $coreSession;
        $this->logger = $logger;
        $this->messageManager = $manager;
        $this->formKey = $formKey;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isCloudHost()
    {
        $host = parse_url($this->getTokenURL(), PHP_URL_HOST);
        return $host == $this->getCloudMingleHost();
    }

    public function getTokenURL()
    {
        return $this->trimURL($this->scopeConfig->getValue(Constant::XML_PATH_WEB_SERVICE_URL));
    }

    public function trimURL($url)
    {
        return trim(rtrim($url, '/'));
    }

    /**
     * @return string
     */
    public function getCloudMingleHost()
    {
        return Constant::CLOUD_MINGLE_HOST;
    }

    /**
     * @return string
     */
    public function getTokenLink()
    {
        $tokenURL = $this->getTokenURL();
        if (!$tokenURL) {
            return '';
        }
        return $tokenURL;
    }

    public function getMingleLink()
    {
        return $this->trimURL($this->scopeConfig->getValue(Constant::XML_PATH_WEB_MINGLE_URL));
    }

    public function getIonAPIServiceLink()
    {
        return $this->scopeConfig->getValue(Constant::XML_PATH_ION_API_SERVICE_URL);
    }

    public function getDomain()
    {
        return $this->scopeConfig->getValue(Constant::XML_PATH_DOMAIN);
    }

    public function isEnable()
    {
        return $this->scopeConfig->getValue(Constant::XML_PATH_ENABLE);
    }

    public function getAccessToken($storeId = null)
    {
        return $this->session->getAccessToken();
    }

    public function getRequestToken()
    {
        $accessToken = '';
        $client = $this->getClient();
        $url = $this->getOauthLink();
        if (!$url) {
            return '';
        }
        $client->setUri($url);
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();
        if (!$clientId || !$clientSecret) {
            return '';
        }
        $credentials['client_id'] = $clientId;
        $credentials['client_secret'] = $clientSecret;
        $credentials['grant_type'] = 'refresh_token';
        $credentials['refresh_token'] = $this->session->getRefreshToken();
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                $this->session->setAccessToken($accessToken);
                $this->session->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            $this->logger->writeLog('API request failed: ' . $e->getMessage());
        }
        return $accessToken;
    }

    /**
     * @return Zend_Http_Client
     * @throws Zend_Http_Client_Adapter_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getClient()
    {
        //Initialize Zend Client Object
        $client = new Zend_Http_Client();
        $options = [
            'ssl' => [
                // Verify server side certificate,
                // do not accept invalid or self-signed SSL certificates
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => false,
                // Capture the peer's certificate
                'capture_peer_cert' => false,
            ],
        ];
        // Create an adapter object and attach it to the HTTP client
        $adapter = new Zend_Http_Client_Adapter_Socket();
        $adapter->setStreamContext($options);
        $client->setAdapter($adapter);
        return $client;
    }

    /**
     * @return string
     */
    public function getOauthLink()
    {
        $oauthURL = $this->getAuthorizeURL();
        if (!$oauthURL) {
            $this->logger->writeLog('Service URL for Token is not configured');
            return '';
        }
        $clientId = $this->getClientId();
        if (!$clientId) {
            $this->logger->writeLog('Client ID is not configured');
            return '';
        }
        return $oauthURL . $this->getParamInURL($clientId);
    }

    public function getAuthorizeURL()
    {
        return $this->scopeConfig->getValue(Constant::XML_PATH_AUTHORIZE_URL);
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
        return $this->scopeConfig->getValue(
            Constant::XML_PATH_WEB_SERVICE_CLIENTID,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    public function getParamInURL($clientId)
    {
        $returnUrl = $this->getReturnUrl();
        return "?client_id=$clientId&max_age=20&prompt=login&nonce=NONCE&response_type=code&redirect_uri=$returnUrl&state=" . $this->getFormKey();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getReturnUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . 'lslogin/index/index/';
    }

    /**
     * @return string
     */
    public function getFormKey()
    {
        try {
            return $this->formKey->getFormKey();
        } catch (LocalizedException $e) {
            $this->logger->writeLog($e->getMessage());
        }
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
        return $this->encryptorInterface->decrypt($this->scopeConfig->getValue(
            Constant::XML_PATH_WEB_SERVICE_CLIENTSECRET,
            ScopeInterface::SCOPE_STORES,
            $storeId
        ));
    }
}
