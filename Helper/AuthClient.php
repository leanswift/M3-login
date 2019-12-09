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

use LeanSwift\Econnect\Helper\Secure;

class AuthClient extends Secure
{

    protected $cloudMingleHost = 'mingle-sso.inforcloudsuite.com';

    const XML_PATH_WEB_MINGLE_URL = 'leanswift_login/authentication/mingle_url';

    const XML_PATH_WEB_SERVICE_URL = 'leanswift_login/authentication/service_url';

    const XML_PATH_ION_URL = 'ion/general_config/service_url';

    const XML_PATH_WEB_SERVICE_CLIENTID = 'leanswift_login/authentication/web_service_clientid';

    const XML_PATH_WEB_SERVICE_CLIENTSECRET = 'leanswift_login/authentication/web_service_clientsecret';

    const XML_PATH_DOMAIN = 'leanswift_login/general/domain_name';

    const XML_PATH_ENABLE = 'leanswift_login/general/enable_login';

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

    /**
     * @return bool
     */
    public function isCloudHost()
    {
        $link = $this->scopeConfig->getValue(self::XML_PATH_WEB_SERVICE_URL);
        $host = parse_url($link, PHP_URL_HOST);
        return $host == $this->getCloudMingleHost();
    }

    /**
     * @return string
     */
    public function getOauthLink()
    {
        $oauthURL = $this->trimURL($this->scopeConfig->getValue(self::XML_PATH_WEB_SERVICE_URL));
        if(!$oauthURL) {
            return  '';
        }
        $clientId = $this->getClientId();
        $isCloud = $this->isCloudHost();
        $returnUrl = $this->getReturnUrl();
        //if it cloud environment
        if ($isCloud) {
            $authorize = '/as/authorization.oauth2';
            $redirect = "redirect_url=$returnUrl";
        }
        //if it is on-premise environment
        else {
            $authorize = '/connect/authorize';
            $redirect = "redirect_uri=$returnUrl";
        }
        $param = "$authorize?client_id=$clientId&response_type=code&$redirect";
        return $oauthURL . $param;
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
            self::XML_PATH_WEB_SERVICE_CLIENTID,
            $this->_dataHelper->getStoreScope(),
            $storeId
        );
    }

    /**
     * @return string
     */
    public function getTokenLink()
    {
        $tokenURL = $this->trimURL($this->scopeConfig->getValue(self::XML_PATH_WEB_SERVICE_URL));
        $isCloud = $this->isCloudHost();
        //if it cloud environment
        if ($isCloud) {
            $token = '/as/token.oauth2';
        }
        //if it is on-premise environment
        else {
            $token = '/connect/token';
        }
        return $tokenURL . $token;
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

    public function getRequestToken()
    {
        $accessToken = '';
        $client = $this->getClient();
        $url = $this->getOauthLink();
        $client->setUri($url);
        $credentials['client_id'] = $this->getClientId();
        $credentials['client_secret'] = $this->getClientSecret();
        $credentials['grant_type'] = 'refresh_token';
        $credentials['refresh_token'] = $this->_session->getRefreshToken();
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
            if ($response->getStatus() == 200) {
                $parsedResult = $response->getBody();
                $responseBody = json_decode($parsedResult, true);
                $accessToken = $responseBody['access_token'];
                $refreshToken = $responseBody['refresh_token'];
                $this->logger()->writeLog('New access token : ' . $accessToken);
                $this->_session->setAccessToken($accessToken);
                $this->_session->setRefreshToken($refreshToken);
            }
        } catch (Exception $e) {
            return $this->logger()->writeLog('API request failed' . $e->getMessage());
        }
        return $accessToken;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->_urlBuilder->getUrl('lslogin');
    }

    /**
     * @return string
     */
    public function getCloudMingleHost()
    {
        return $this->cloudMingleHost;
    }

    public function trimURL($url)
    {
        return trim(rtrim($url, '/'));
    }
}
