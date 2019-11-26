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
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;

class Authentication
{
    /**
     * @var AuthClient
     */
    private $auth;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @param AuthClient            $authClient
     * @param CustomerFactory       $customerFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AuthClient $authClient,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        SessionManagerInterface $coreSession,
        Adapter $adapter
    ) {
        $this->auth = $authClient;
        $this->storeManager = $storeManager;
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
        $credentials['client_id']= $this->auth->getClientId();
        $credentials['client_secret']= $this->auth->getClientSecret();
        $credentials['grant_type']= 'authorization_code';
        $credentials['code']= $code;
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $this->auth->logger()->writeLog('New access token : ' . $accessToken);
                $this->_coreSession->start();
                $this->_coreSession->setAccessToken($accessToken);
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
        $url = $mingleUrl.'/api/v1/mingle/go/User/Detail';
        $client->setHeaders(
            ['Authorization' => 'Bearer ' . $accessToken]
        );
        $client->setUri($url);
        $client->setRawData('', 'application/json');
        $client->setConfig(['maxredirects' => 3, 'timeout' => 20, 'keepalive' => true]);
        $userDetailList = false;
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
        return $userDetailList;

    }

    public function createCustomer($userDetailList)
    {
        if ($userDetailList) {
            $username = false;
            $usercode = $userDetailList['UserName'];
            $data['EUID'] = $usercode;
            $url = $this->auth->getIonLink()."/MNS150MI/GetUserByEuid?EUID=$usercode";
            $method = '';
            $result = $this->sendRequest('', $method, 20, $url, null,'GET');
            if($result)
            {
                $recordInfo = $result['MIRecord'][0]['NameValue'];
                foreach ($recordInfo as $record)
                {
                    if($record['Name'] == 'USID')
                    {
                        $username = $record['Value'];
                        break;
                    }
                }
            }
            if (!$username) {
                return false;
            }
            // Get Website ID
            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

            // Instantiate object (this is the most important part)
            $customer   = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);

            $email = $userDetailList['Email'];
            $firstName = $userDetailList['FirstName'];
            $lastName = $userDetailList['LastName'];
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
            } catch (\Exception $e) {
                $this->auth->logger()->writeLog($e->getMessage());
            }

            return true;
            //$customer->sendNewAccountEmail();
        }
    }
    public function requestToken($code)
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getOauthLink();
        $client->setUri($url);
        $callbackUrl = urlencode(urldecode('http://127.0.0.1/econnect/ce/231/login/'));
        $credentials['client_id']= $this->auth->getClientId();
        $credentials['client_secret']= $this->auth->getClientSecret();
        $credentials['grant_type']= 'authorization_code';
        $credentials['redirect_ur']= $callbackUrl;
        $credentials['code']= $code;
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                var_dump($response);
                $accessToken = $responseBody['access_token'];
                $this->auth->logger()->writeLog('New access token : ' . $accessToken);
            }
        } catch (Exception $e) {
            return $this->auth->logger()->writeLog('API request failed' . $e->getMessage());
        }
        return $accessToken;

    }

    public function sendRequest($data, $method, $timeout=20, $url=null, $client=null,$requestType='POST')
    {
        $responseBody = false;
        if ($client == null) {
            $client = $this->auth->getClient();
        }
        if ($url == null) {
            $url = $this->auth->getIonLink().$method;
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