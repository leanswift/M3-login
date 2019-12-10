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

namespace LeanSwift\Login\Model;

use Exception;
use LeanSwift\Login\Helper\AuthClient;
use LeanSwift\Login\Helper\Constant;
use LeanSwift\Login\Helper\Logger;
use LeanSwift\Login\Model\Api\Adapter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Authentication
{

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
     * Authentication constructor.
     *
     * @param AuthClient                  $authClient
     * @param CustomerFactory             $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SessionManagerInterface     $coreSession
     * @param Adapter                     $adapter
     */
    public function __construct(
        AuthClient $authClient,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        SessionManagerInterface $coreSession,
        Logger $logger
    ) {
        $this->auth = $authClient;
        $this->customerFactory = $customerFactory;
        $this->customerRepo = $customerRepository;
        $this->_coreSession = $coreSession;
        $this->logger = $logger;
    }

    public function generateToken($code, $timeout=60)
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getTokenLink();
        if(!$url) {
            $this->logger->writeLog('Service URL for Token is not configured');
            return '';
        }
        $client->setUri($url);
        $clientId = $this->auth->getClientId();
        $clientSecret = $this->auth->getClientSecret();
        if(!$clientId || !$clientSecret) {
            $this->logger->writeLog('Please Check Oauth credentials, there might be a problem on creating access token !');
            return '';
        }
        $credentials['client_id'] = $clientId;
        $credentials['client_secret'] = $clientSecret;
        $credentials['grant_type'] = 'authorization_code';
        $credentials['code'] = $code;
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => $timeout]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                $this->logger->writeLog('New access token : ' . $accessToken);
                $this->_coreSession->start();
                $this->_coreSession->setAccessToken($accessToken);
                $this->_coreSession->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            $this->logger->writeLog('API request failed' . $e->getMessage());
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
        if(!$mingleUrl)
        {
          return '';
        }
        $userDetailList = $this->getUserDetails($mingleUrl, $accessToken);
        if (!empty($userDetailList)) {
            $userCode = $userDetailList['UserName'];
            $email = $userDetailList['Email'];
            $firstName = $userDetailList['FirstName'];
            $lastName = $userDetailList['LastName'];
            $customerData = [
                'email'     => $email,
                'firstname' => $firstName,
                'lastname'  => $lastName,
                'username'  => ''
            ];
            $data['EUID'] = $userCode;
            $customerData['username'] = $this->getUserNameDetail($accessToken, $userCode);
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
        $responseBody = $this->sendRequest($params,'POST');
        if(!empty($responseBody))
        {
            return $responseBody['UserDetailList'][0];
        }
        return [];
    }

    /**
     * @param $token
     * @param string $userCode
     * @param string $method
     * @param string $userId
     * @return string
     */
    public function getUserNameDetail($token, $userCode='', $method = Constant::GET_USER_BY_EUID, $userId= Constant::USID)
    {
        $userName = '';
        $params['url'] = $this->auth->getIonLink();
        $params['token'] = $token;
        if($userCode)
        {
            $params['method'] = $method.$userCode;
        }
        else {
            $params['method'] = $method;
        }
        $recordInfo = $this->sendRequest($params);
        if(!empty($recordInfo))
        {
            array_walk_recursive($recordInfo, function ($value, $key) use (&$userName, &$userId) {
                if($key == $userId) {
                    $userName = $value;
                }
            });
        }
        return $userName;
    }

    public function sendRequest($params, $requestType = 'GET', $timeout=20)
    {
        $responseBody = false;
        $beforeTime = microtime(true);
        if (!isset($params['client'])) {
            $client = $this->auth->getClient();
        }
        else {
            $client = $params['client'];
        }
        $url = $params['url'].$params['method'];
        $accessToken = isset($params['token']) ? $params['token'] : $this->_coreSession->getAccessToken();
        $client->setUri($url);
        $client->setHeaders(
            ['Authorization' => 'Bearer ' . $accessToken]
        );
        $client->setHeaders(['accept' => 'application/json;charset=utf-8']);
        if (isset($params['data'])) {
            $data = json_encode($params['data']);
        }
        else {
            $data = '{}';
        }
        $client->setRawData($data, 'application/json');
        $client->setConfig(['maxredirects' => 3, 'timeout' => $timeout, 'keepalive' => true]);
        try {
            $response = $client->request($requestType);
            $parsedResult = $response->getBody();
            $afterTime = microtime(true);
            if ($response->getStatus() == 200) {
                $rTime = $afterTime - $beforeTime;
                $responseBody = json_decode($parsedResult, true);
            }
            $this->logger->writeLog($params['method'] . ' Transaction Data:' . $data . 'Response: ' . $parsedResult
                . 'Response Time in secs:'
                . $rTime);
        } catch (Exception $e) {
            $this->logger->writeLog($params['method'] . ' Transaction Data:' . 'API request failed - ' . $e->getMessage());
        }
        return $responseBody;
    }

    public function requestToken()
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getOauthLink();
        if(!$url) {
            return '';
        }
        $client->setUri($url);
        $clientId = $this->auth->getClientId();
        $clientSecret = $this->auth->getClientSecret();
        if(!$clientId || !$clientSecret) {
            return '';
        }
        $credentials['client_id'] = $clientId;
        $credentials['client_secret'] = $clientSecret;
        $credentials['grant_type'] = 'refresh_token';
        $credentials['refresh_token'] = $this->_coreSession->getRefreshToken();
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                $this->logger->writeLog('New access token : ' . $accessToken);
                $this->_coreSession->setAccessToken($accessToken);
                $this->_coreSession->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            $this->logger->writeLog('API request failed' . $e->getMessage());
            return false;
        }
        return $accessToken;
    }
}
