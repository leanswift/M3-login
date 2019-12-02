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

use LeanSwift\Login\Helper\AuthClient;
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
    private $erpApi;

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
        Adapter $adapter
    ) {
        $this->auth = $authClient;
        $this->customerFactory = $customerFactory;
        $this->customerRepo = $customerRepository;
        $this->_coreSession = $coreSession;
        $this->erpApi = $adapter;
    }

    public function generateToken($code)
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getTokenLink();
        $client->setUri($url);
        $credentials['client_id'] = $this->auth->getClientId();
        $credentials['client_secret'] = $this->auth->getClientSecret();
        $credentials['grant_type'] = 'authorization_code';
        $credentials['code'] = $code;
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                $this->auth->logger()->writeLog('New access token : ' . $accessToken);
                $this->_coreSession->start();
                $this->_coreSession->setAccessToken($accessToken);
                $this->_coreSession->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            $this->auth->logger()->writeLog('API request failed' . $e->getMessage());
        }

        return $accessToken;
    }

    public function getUserName($accessToken)
    {
        if (!$accessToken) {
            return false;
        }
        $client = $this->auth->getClient();
        $mingleUrl = $this->auth->getMingleLink();
        $url = $mingleUrl . '/api/v1/mingle/go/User/Detail';
        $client->setHeaders(
            ['Authorization' => 'Bearer ' . $accessToken]
        );
        $client->setUri($url);
        $client->setRawData('', 'application/json');
        $client->setConfig(['maxredirects' => 3, 'timeout' => 20, 'keepalive' => true]);
        $userDetailList = false;
        $username = false;
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $userDetailList = $responseBody['UserDetailList'][0];
            }
        } catch (Exception $e) {
            $this->auth->logger()->writeLog('API request failed' . $e->getMessage());
        }
        if ($userDetailList) {
            $username = false;
            $usercode = $userDetailList['UserName'];
            $email = $userDetailList['Email'];
            $firstName = $userDetailList['FirstName'];
            $lastName = $userDetailList['LastName'];
            $customerData = [
                'email'     => $email,
                'firstname' => $firstName,
                'lastname'  => $lastName,
            ];
            $data['EUID'] = $usercode;
            $url = $this->auth->getIonLink() . "/MNS150MI/GetUserByEuid?EUID=$usercode";
            $method = '';
            $recordInfo = $this->sendRequest('', $method, 20, $url, null, 'GET');
            if ($recordInfo['results']) {
                $username = $recordInfo['results'][0]['records'][0]['USID'];
                $customerData['username'] = $username;
            }
        }

        return $customerData;
    }

    public function sendRequest($data, $method, $timeout = 20, $url = null, $client = null, $requestType = 'POST')
    {
        $responseBody = false;
        if ($client == null) {
            $client = $this->auth->getClient();
        }
        if ($url == null) {
            $url = $this->auth->getIonLink() . $method;
        }
        $accessToken = $this->_coreSession->getAccessToken();
        $client->setUri($url);
        $client->setHeaders(
            ['Authorization' => 'Bearer ' . $accessToken]
        );
        $client->setHeaders(['accept' => 'application/json;charset=utf-8']);
        if ($data) {
            $data = json_encode($data);
        }
        $client->setRawData('{}', 'application/json');
        $client->setConfig(['maxredirects' => 3, 'timeout' => $timeout, 'keepalive' => true]);
        try {
            $response = $client->request($requestType);
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
            }
        } catch (Exception $e) {
            $this->auth->logger()->writeLog('API request failed' . $e->getMessage());
        }

        return $responseBody;
    }

}
