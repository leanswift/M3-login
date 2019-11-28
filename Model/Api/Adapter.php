<?php
/**
 * LeanSwift eConnect Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the LeanSwift eConnect Extension License
 * that is bundled with this package in the file LICENSE.txt located in the Connector Server.
 *
 * DISCLAIMER
 *
 * This extension is licensed and distributed by LeanSwift. Do not edit or add to this file
 * if you wish to upgrade Extension and Connector to newer versions in the future.
 * If you wish to customize Extension for your needs please contact LeanSwift for more
 * information. You may not reverse engineer, decompile,
 * or disassemble LeanSwift Connector Extension (All Versions), except and only to the extent that
 * such activity is expressly permitted by applicable law not withstanding this limitation.
 *
 * @copyright   Copyright (c) 2019 LeanSwift Inc. (http://www.leanswift.com)
 * @license     http://www.leanswift.com/license/connector-extension
 */

namespace LeanSwift\Login\Model\Api;

use Exception;
use LeanSwift\Econnect\Helper\Constant;
use LeanSwift\Econnect\Helper\Data;
use LeanSwift\Econnect\Helper\Ion;
use LeanSwift\Login\Helper\AuthClient;
use LeanSwift\Econnect\Helper\Secure;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Zend_Http_Client;
use Zend_Http_Client_Exception;

class Adapter extends AbstractModel
{

    const TXN_TYPE = 'txnType';

    const TIMEOUT_VALUE = 1000;

    const DATE_FORMAT_CODE = 'YMD8';

    const READ_TIMEOUT_MILLI_DURATION = 300000;

    /**
     * LeanSwift Helper Data
     *
     * @var Data
     */
    protected $_helperData = null;

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
     * LeanSwift Helper Ion
     *
     * @var Ion
     */
    protected $_ionHelper;

    /**
     * Adapter constructor.
     *
     * @param Data   $helperData
     * @param Secure $helperSecure
     * @param Ion    $ion
     */
    public function __construct(Data $helperData, Secure $helperSecure, Ion $ion, AuthClient $authClient)
    {
        $this->_helperData = $helperData;
        $this->_ionHelper = $ion;
        $this->_helperSecure = $helperSecure;
        $this->helperAuth = $authClient;
    }

    /**
     * @param      $transaction
     * @param      $requestData
     * @param int  $timeout
     * @param null $storeId
     *
     * @return null|string
     * @throws NoSuchEntityException
     */
    public function _sendRequest($transaction, $requestData, $timeout = 120, $storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->_ionHelper->getStoreId();
        }

        $responseBody = null;
        $serviceUrl = $this->_ionHelper->getM3ServiceUrl();
        if (empty($serviceUrl)) {
            $this->_ionHelper->writeLog('Service Url Empty!');
            return false;
        }

        //Request data preparation
        $data = $this->_prepareRequest($transaction, $requestData, $storeId);
        $txnType = $data[self::TXN_TYPE];
        unset($data[self::TXN_TYPE]);

        //Overwrite the read timeout value
        $timeout = $timeout * self::TIMEOUT_VALUE;
        if ($timeout > self::TIMEOUT_VALUE) {
            $data['readTimeoutMillis'] = $timeout;
        }

        //Convert array to json
        $data = json_encode($data);
        try {
            //Client object with bearer authorization
            $client = $this->setAccessToken();
            if ($client) {
                $client->setUri($serviceUrl);
                $client->setRawData($data, 'application/json');
                $client->setConfig(['maxredirects' => 5, 'timeout' => $timeout, 'keepalive' => true]);
                $response = $client->request('POST');
                $beforeTime = microtime(true);

                //Case to handle invalid access token
                if ($response && $response->getStatus() == 401) {
                    //Initialize new access token
                    $this->_ionHelper->writeLog('Access token Invalid!', false);
                    $this->_ionHelper->writeLog('Initialise new access token !', false);
                    $customer = $this->_helperData->getCustomerSession();
                    if ($customer->isLoggedIn()) {
                        $accessToken = $this->helperAuth->createAccessToken();
                    } else {
                        $this->_helperSecure->createAccessToken($storeId);
                        $accessToken = $this->_helperSecure->getAccessToken();
                    }


                    if ($accessToken == '' || $accessToken == null) {
                        $msg = 'Please Check Oauth credentials, there might be a problem on creating access token !';
                        $this->_ionHelper->writeLog($msg);
                        return null;
                    }

                    $client = $this->setAccessToken();
                    $client->setUri($serviceUrl);
                    $client->setRawData($data, 'application/json');
                    $client->setConfig(['maxredirects' => 5, 'timeout' => $timeout, 'keepalive' => true]);
                    $response = $client->request('POST');
                }

                $afterTime = microtime(true);
                $rTime = $afterTime - $beforeTime;
                $responseBody = null;
                $errorMessage = false;
                if ($response && $response->getStatus() == 200) {
                    $responseBody = $response->getBody();
                    $this->_ionHelper->writeLog('ION Response : ' . $responseBody);

                    //Converting the response format
                    $parsedResult = json_decode($responseBody, true);
                    if (is_array($parsedResult) && isset($parsedResult[Constant::RESULTS])) {
                        $responseData = null;
                        $k = 0;
                        foreach ($parsedResult[Constant::RESULTS] as $resultData) {
                            if ($txnType == Constant::API_TXN_LIST) {
                                $responseData[Constant::OUTPUT] = $resultData[Constant::RECORDS];
                                if (array_key_exists('errorMessage', $resultData)) {
                                    $errorMessage = $resultData['errorMessage'];
                                    $responseData['error'] = $errorMessage;
                                }
                            } else {
                                $responseData[Constant::OUTPUT] = $resultData[Constant::RECORDS];
                                if (array_key_exists('errorMessage', $resultData)) {
                                    $errorMessage = $resultData['errorMessage'];
                                    $responseData['error'] = $errorMessage;
                                }
                            }
                            $k++;
                        }

                        if (is_array($responseData)) {
                            $responseBody = json_encode($responseData);
                        }
                    }
                } else {
                    $responseBody = ['request' => $data];
                    $responseBody[Constant::KEY_RESULT] = [
                        Constant::KEY_ERROR => $errorMessage,
                        Constant::KEY_CODE  => $response->getStatus(),
                    ];
                    $responseBody = json_encode($responseBody);
                }

                $this->_ionHelper->writeLog($transaction . ' Transaction Data:' . $data . 'Response: ' . $responseBody
                    . '-' . $errorMessage . "\r\n"
                    . 'Response Time in secs:'
                    . $rTime);
            }
        } catch (Exception $e) {
            $debugData = ['request' => $data];
            $debugData[Constant::KEY_RESULT] = [
                Constant::KEY_ERROR => $e->getMessage(),
                Constant::KEY_CODE  => $e->getCode(),
            ];
            $this->_ionHelper->writeLog('Service Link Error - eConnect Transaction Related' . "\r\n"
                . json_encode($debugData, true));
        }

        return $responseBody;
    }

    /**
     * @param      $transactionInfo
     * @param      $record
     * @param null $storeId
     *
     * @return mixed
     */
    protected function _prepareRequest($transactionInfo, $record, $storeId = null)
    {
        $requestData = [];
        //Separate the program and transaction
        $transactionInfo = array_filter(explode('/', $transactionInfo));
        $txnType = $transactionInfo[1];
        //program always in second array because the format is already defined.
        $program = $transactionInfo[2];
        //transaction always in third array because the format is already defined.
        $transaction = $transactionInfo[3];
        //Transaction attribute preparation
        $m3user = $this->_ionHelper->getM3User($storeId);
        $customer = $this->_helperData->getCustomerSession();
        if ($customer->isLoggedIn()) {
            $m3user = $customer->getCustomer()->getUsername();
        }
        $data['program'] = $program;
        //M3 user which can be defined in system configuration.
        $data['m3User'] = $m3user;
        //If storeID exist then pick the company and division from respective store.
        if ($storeId) {
            $data[Constant::KEY_CONO] = (int)$this->_ionHelper->getCompany($storeId);
            $data[Constant::KEY_DIVI] = $this->_ionHelper->getDivision($storeId);
        }

        //Standard date format
        $data['dateFormat'] = self::DATE_FORMAT_CODE;
        $data['readTimeoutMillis'] = self::READ_TIMEOUT_MILLI_DURATION;
        $data['excludeEmptyValues'] = true;
        $data['rightTrim'] = true;
        $data['maxReturnedRecords'] = 1000;

        $data[self::TXN_TYPE] = $txnType;
        if ($txnType == Constant::API_TXN_LIST) {
            foreach ($record[$txnType] as $recordData) {
                $requestData[] = ['transaction' => $transaction, 'record' => $recordData];
            }
        } else {
            $requestData = [['transaction' => $transaction, 'record' => $record]];
        }
        $data['transactions'] = $requestData;

        return $data;
    }

    /**
     * @return Zend_Http_Client
     * @throws NoSuchEntityException
     * @throws Zend_Http_Client_Exception
     */
    protected function setAccessToken()
    {
        $client = $this->_helperSecure->getClient();
        $storeId = $this->_ionHelper->getStoreId();
        $client->setConfig(['timeout' => 60]);
        $customer = $this->_helperData->getCustomerSession();
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
