<?php

namespace LeanSwift\Login\Plugin\Customer;

use Laminas\Stdlib\Parameters;
use Magento\Customer\Controller\Account\LoginPost as CustomerLoginPost;

/**
 * Class LoginPost
 * @package LeanSwift\Login\Plugin\Customer
 */
class LoginPost
{
    protected $parametersInterface;

    /**
     * LoginPost constructor.
     * @param Parameters $parameters
     */
    public function __construct(
        Parameters $parameters
    ) {
        $this->parametersInterface = $parameters;
    }

    /**
     * Add Dummy Password
     *
     * @inheirtDoc
     * @param CustomerLoginPost $subject
     */
    public function beforeExecute(CustomerLoginPost $subject)
    {
        $subject->getRequest()->setParams(['form_key' => $subject->getRequest()->getParam('form_key')]);
        $loginData = $subject->getRequest()->getPost('login');
        if (!$loginData['password']) {
            $loginData['password'] = $this->getDummyPassword();
            $this->parametersInterface->fromArray(['login' => $loginData]);
            $subject->getRequest()->setPost($this->parametersInterface);
        }
    }

    public function getDummyPassword()
    {
        return md5(uniqid(rand(), true));
    }
}
