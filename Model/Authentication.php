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



class Authentication
{
    /**
     * @var AuthClient
     */
    private $auth;

    public function __construct(AuthClient $authClient)
    {
        $this->auth = $authClient;
    }

    public function generateToken($code)
    {
        $accessToken = '';
        $client = $this->auth->getClient();
        $url = $this->auth->getTokenLink();
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
                $accessToken = $responseBody['access_token'];
                $this->auth->logger()->writeLog('New access token : ' . $accessToken);
            }
        } catch (Exception $e) {
            $this->auth->logger()->writeLog('API request failed' . $e->getMessage());
        }

        return $accessToken;

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
die;
        return $accessToken;

    }
    public function checklogin()
    {
        $consumerKey = 'LEANSWIFT_TST~1g10WVgepkEDqeIUrCcjzFWxLJRrUtwDPiuxKhKRL5Y';
        $callbackUrl = urlencode(urldecode('http://127.0.0.1/econnect/ce/231/econnect/'));
        $consumerSecret = 'rMDxHJV0IAHckObLHAKIwrhcj3gOZVrd9NLC_IV1n8efRCKu6Wv9s5_XhkTB5ZZHz1iD__VUVcYaGsroG9h7wQ';
        $magentoBaseUrl = rtrim('https://mingle-sso.inforcloudsuite.com:443/LEANSWIFT_TST/as/authorization.oauth2');
        $oauthVerifier = 'authorization_code';

        /*
        OAuthClientRequest request = OAuthClientRequest
            .authorizationProvider("https://mingle-sso.inforcloudsuite.com:443/LEANSWIFT_TST/as/authorization.oauth2")
            .setClientId("ACME_PRD~QxG91-i82CO4P7L5R1YR4YwdOy
        Ww5caGh0UqkvqYrUY")
            .setRedirectURI("http://sample-oauth2-client.in
        for.com:8080/SampleAppOAuth2/redirect"
                .setResponseType("code")
                .buildQueryMessage();*/
        /*$credentials = new \OAuth\Common\Consumer\Credentials($consumerKey, $consumerSecret, $callbackUrl);
        $oAuthClient = new OauthClient($credentials);*/


        //Initialize Zend Client Object
        $client = new \Zend_Http_Client();
        $options = [
            'ssl' => [
                // Verify server side certificate,
                // do not accept invalid or self-signed SSL certificates
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => false,
                // Capture the peer's certificate
                'capture_peer_cert' => false,
            ],
        ];
        // Create an adapter object and attach it to the HTTP client
        $adapter = new \Zend_Http_Client_Adapter_Socket();
        $adapter->setStreamContext($options);
        $client->setAdapter($adapter);
        $url = 'https://mingle-sso.inforcloudsuite.com:443/LEANSWIFT_TST/as/token.oauth2';
        $client->setUri($url);
        /*        $credentials['client_id']= $consumerKey;
                $credentials['client_secret']= $consumerSecret;
                $credentials['grant_type']= 'authorization_code';
                $credentials['redirect_ur']= $callbackUrl;
                $credentials['code']= 'Ck0rkQdEhqpdHIhBOBD0UYEUkbX9HOBG1IEHxaWN';
                $client->setParameterPost($credentials);
                $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);*/

        $credentials['client_id']= $consumerKey;
        $credentials['client_secret']= $consumerSecret;
        $credentials['grant_type']= 'refresh_token';
        $credentials['refresh_token']= 'NabD4EVwM3VCgoJqxY174oFlyHlMbco0SkMKN8F7K0';
        $client->setParameterPost($credentials);
        $client->setConfig(['maxredirects' => 3, 'timeout' => 60]);
        try {
            $response = $client->request('POST');
        } catch (Exception $e) {
            return $this->_dataHelper->writeLog('API request failed' . $e->getMessage());
        }
    }
}