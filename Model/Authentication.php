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

namespace LeanSwift\Login\Model;

use Exception;
use LeanSwift\Login\Helper\AuthClient;
use LeanSwift\Login\Helper\Constant;
use LeanSwift\Login\Helper\Logger;
use LeanSwift\Login\Model\Api\Adapter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Zend_Http_Client_Adapter_Exception;
use Zend_Http_Client_Exception;

/**
 * Class Authentication
 * @package LeanSwift\Login\Model
 */
class Authentication
{
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
    /**
     * @var AuthClient
     */
    private $auth;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepo;
    /**
     * @var Adapter
     */
    private $logger;
    /**
     * @var mixed|string
     */
    private $authKey;

    /**
     * Authentication constructor.
     *
     * @param AuthClient $authClient
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SessionManagerInterface $coreSession
     * @param Logger $logger
     */
    public function __construct(
        AuthClient $authClient,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        SessionManagerInterface $coreSession,
        Logger $logger,
        $authkey = 'Email'
    ) {
        $this->auth = $authClient;
        $this->customerFactory = $customerFactory;
        $this->customerRepo = $customerRepository;
        $this->_coreSession = $coreSession;
        $this->logger = $logger;
        $this->authKey = $authkey;
    }

    /**
     * @param $code
     * @param int $timeout
     * @return string
     * @throws Zend_Http_Client_Adapter_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function generateToken($code, $timeout = 60)
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getTokenLink();
        if (!$url) {
            $this->logger->writeLog('Service URL for Token is not configured');
            return '';
        }
        $client->setUri($url);
        $clientId = $this->auth->getClientId();
        $clientSecret = $this->auth->getClientSecret();
        if (!$clientId || !$clientSecret) {
            $this->logger->writeLog('Please Check Oauth credentials, there might be a problem on creating access token !');
            return '';
        }
        $credentials['client_id'] = $clientId;
        $credentials['client_secret'] = $clientSecret;
        $credentials['grant_type'] = 'authorization_code';
        $credentials['code'] = $code;
        $credentials['redirect_uri'] = $this->auth->getReturnUrl();
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => $timeout]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                //                $this->logger->writeLog('New access token : ' . $accessToken);
                $this->_coreSession->start();
                $this->_coreSession->setAccessToken($accessToken);
                $this->_coreSession->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            $this->logger->writeLog('API request failed: ' . $e->getMessage());
        }
        return $accessToken;
    }

    /**
     * @param $accessToken
     * @return array|string
     */
    public function getUserName($accessToken)
    {
        $customerData = [];
        $mingleUrl = $this->auth->getMingleLink();
        if (!$mingleUrl) {
            return '';
        }
        $userDetailList = $this->getUserDetails($mingleUrl, $accessToken);
        if (!empty($userDetailList)) {
            $authorizeKey = $this->authKey;
            $userCode = $userDetailList['UserName'];
            $email = $userDetailList[$authorizeKey] ?? '';
            $firstName = $userDetailList['FirstName'];
            $lastName = $userDetailList['LastName'];
            $personID = $userDetailList['PersonId'];
            $customerData = [
                'email' => $email,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'username' => '',
                'personId' => $personID
            ];
            $isCloud = $this->auth->isCloudHost();
            if ($isCloud) {
                $customerData['username'] = $this->getUserNameDetail($accessToken, $userCode);
            } else {
                $customerData['username'] = $userDetailList['PersonId'];
            }
            //$data['EUID'] = $userCode;
        }
        return $customerData;
    }

    /**
     * @param $mingleUrl
     * @param $accessToken
     * @param string $method
     * @return array
     */
    public function getUserDetails($mingleUrl, $accessToken, $method = Constant::MINGLE_USER_DETAIL)
    {
        $params['url'] = $mingleUrl;
        $params['method'] = $method;
        $params['token'] = $accessToken;
        $responseBody = $this->sendRequest($params);
        if (!empty($responseBody)) {
            return $responseBody['UserDetailList'][0];
        }
        return [];
    }

    public function sendRequest($params, $requestType = 'GET', $timeout = 20)
    {
        $responseBody = false;
        $beforeTime = microtime(true);
        if (!isset($params['client'])) {
            $client = $this->auth->getClient();
        } else {
            $client = $params['client'];
        }
        $url = $params['url'] . $params['method'];
        $accessToken = isset($params['token']) ? $params['token'] : $this->_coreSession->getAccessToken();
        $client->setUri($url);
        $client->setHeaders(['Authorization' => 'Bearer ' . $accessToken]);
        $client->setHeaders(['accept' => 'application/json;charset=utf-8']);
        if (isset($params['data'])) {
            $data = json_encode($params['data']);
        } else {
            $data = '';
        }
        $client->setRawData($data, 'application/json');
        $client->setConfig(['maxredirects' => 3, 'timeout' => $timeout, 'keepalive' => true]);
        $afterTime = microtime(true);
        $rTime = $afterTime - $beforeTime;
        try {
            $response = $client->request($requestType);
            $parsedResult = $response->getBody();
            if ($response->getStatus() == 200) {
                $responseBody = json_decode($parsedResult, true);
            }
            $this->logger->writeLog($params['method'] . ' Transaction Data:' . $data . 'Response: ' . $parsedResult . 'Response Time in secs:' . $rTime);
        } catch (Exception $e) {
            $this->logger->writeLog($params['method'] . ' Transaction Data:' . 'API request failed:  - ' . $e->getMessage());
        }
        return $responseBody;
    }

    /**
     * @param $token
     * @param string $userCode
     * @param string $method
     * @param string $userId
     * @return string
     */
    public function getUserNameDetail(
        $token,
        $userCode = '',
        $method = Constant::GET_USER_BY_EUID,
        $userId = Constant::USID
    ) {
        $userName = '';
        $params['url'] = $this->auth->getIonAPIServiceLink();
        $params['token'] = $token;
        if ($userCode) {
            $params['method'] = $method . $userCode;
        } else {
            $params['method'] = $method;
        }
        $recordInfo = $this->sendRequest($params);
        if (!empty($recordInfo)) {
            array_walk_recursive($recordInfo, function ($value, $key) use (&$userName, &$userId) {
                if ($key == $userId) {
                    $userName = $value;
                }
            });
        }
        return $userName;
    }

    public function requestToken($timeout = 60)
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getOauthLink();
        if (!$url) {
            return '';
        }
        $client->setUri($url);
        $clientId = $this->auth->getClientId();
        $clientSecret = $this->auth->getClientSecret();
        if (!$clientId || !$clientSecret) {
            return '';
        }
        $credentials['client_id'] = $clientId;
        $credentials['client_secret'] = $clientSecret;
        $credentials['grant_type'] = 'refresh_token';
        $credentials['refresh_token'] = $this->_coreSession->getRefreshToken();
        $client->setParameterPost($credentials);
        try {
            $client->setConfig(['maxredirects' => 3, 'timeout' => $timeout]);
        } catch (Zend_Http_Client_Exception $e) {
        }
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                //                $this->logger->writeLog('New access token : ' . $accessToken);
                $this->_coreSession->setAccessToken($accessToken);
                $this->_coreSession->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            $this->logger->writeLog('API request failed: ' . $e->getMessage());
            return false;
        }
        return $accessToken;
    }
}
