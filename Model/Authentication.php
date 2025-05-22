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

declare(strict_types=1);

namespace LeanSwift\Login\Model;

use LeanSwift\EconnectBase\Model\Connect\Ion;
use LeanSwift\Login\Helper\AuthClient;
use LeanSwift\Login\Helper\Constant;
use Magento\Framework\HTTP\AsyncClient\Request;

class Authentication
{
    private AuthClient $authClient;
    private Ion $ion;
    private string $authkey;

    public function __construct(
        AuthClient $authClient,
        Ion $ion,
        $authkey = 'Email'
    ) {
        $this->authClient = $authClient;
        $this->ion = $ion;
        $this->authkey = $authkey;
    }

    public function getUserName($code='')
    {
        $accessToken = $this->authClient->getRequestToken($code);
        $customerData = [];
        $ifsUrl = $this->authClient->getIfsLink();
        if (!$ifsUrl) {
            return '';
        }
        $userDetailList = $this->getUserDetails($ifsUrl, $accessToken);
        if (!empty($userDetailList)) {
            $authorizeKey = $this->authkey;
            $userCode = $userDetailList['id'];
            $email = $userDetailList['emails'][0]['value'] ?? '';
            $firstName = $userDetailList['name']['givenName'];
            $lastName = $userDetailList['name']['familyName'];
            $personID = $userDetailList['ifsPersonId'];
            $customerData = [
                'email' => $email,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'personId' => $personID
            ];
            $isCloud = $this->authClient->isCloudHost();
            if ($isCloud) {
                $customerData['username'] = $this->getUserNameDetail($userCode);
            } else {
                $customerData['username'] = $userDetailList['ifsPersonId'];
            }
        }
        return $customerData;
    }

    public function getUserDetails($ifsUrl, $accessToken = '')
    {
        $method = Constant::IFS_USER_DETAIL;
        $serviceURL = $ifsUrl.$method;
        $output = $this->ion->sendDirectRequest($serviceURL,[],'GET',$accessToken);
        $responseBody =  json_decode($output->asString(), true);
        if (!empty($responseBody)) {
            return $responseBody['response']['userlist'][0];
        }
        return [];
    }

    public function getUserNameDetail(
        $userCode,
        $method = Constant::GET_USER_BY_EUID,
        $userId = Constant::USID
    ) {
        $userName = '';
        $serviceURL =  $this->authClient->getIonAPIServiceLink(). $method;
        if ($userCode) {
            $serviceURL = $serviceURL . $userCode;
        }
        $accessToken = $this->authClient->getAccessToken();
        $output = $this->ion->sendDirectRequest($serviceURL, [], Request::METHOD_GET,$accessToken);
        $recordInfo =  json_decode($output->asString(), true);
        if (!empty($recordInfo)) {
            array_walk_recursive($recordInfo, function ($value, $key) use (&$userName, &$userId) {
                if ($key == $userId) {
                    $userName = $value;
                }
            });
        }
        return $userName;
    }
}
