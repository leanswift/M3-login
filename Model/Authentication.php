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

    public function getUserName()
    {
        $customerData = [];
        $mingleUrl = $this->authClient->getMingleLink();
        if (!$mingleUrl) {
            return '';
        }
        $userDetailList = $this->getUserDetails($mingleUrl);
        if (!empty($userDetailList)) {
            $authorizeKey = $this->authkey;
            $userCode = $userDetailList['UserName'];
            $email = $userDetailList[$authorizeKey] ?? '';
            $firstName = $userDetailList['FirstName'];
            $lastName = $userDetailList['LastName'];
            $personID = $userDetailList['PersonId'];
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
                $customerData['username'] = $userDetailList['PersonId'];
            }
        }
        return $customerData;
    }

    public function getUserDetails($mingleUrl,  $method = Constant::MINGLE_USER_DETAIL)
    {
        $serviceURL = $mingleUrl.$method;
        $output = $this->ion->sendDirectRequest($serviceURL,[]);
        $responseBody =  json_decode($output->asString(), true);
        if (!empty($responseBody)) {
            return $responseBody['UserDetailList'][0];
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
        $output = $this->ion->sendDirectRequest($serviceURL, [], Request::METHOD_GET);
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
