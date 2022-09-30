<?php

namespace LeanSwift\Login\Plugin\Customer;

use Laminas\Stdlib\Parameters;
use LeanSwift\Login\Helper\AuthClient;
use Magento\Customer\Controller\Account\LoginPost as CustomerLoginPost;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Class LoginPost
 * @package LeanSwift\Login\Plugin\Customer
 */
class LoginPost
{
    protected $parametersInterface;
    private AuthClient $authClient;
    private RedirectFactory $resultRedirectFactory;
    private DataPersistorInterface $dataPersistor;

       public function __construct(
        Parameters $parameters,
        AuthClient $authClient,
        RedirectFactory $resultRedirectFactory,
        DataPersistorInterface $dataPersistor
    ) {
        $this->parametersInterface = $parameters;
        $this->authClient = $authClient;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     *
     * @inheirtDoc
     * @param CustomerLoginPost $subject
     */
    public function aroundExecute(CustomerLoginPost $subject, callable $proceed)
    {
        $isEnable = $this->authClient->isEnable();
        if(!$isEnable)
        {
            return $proceed();
        }
        return $this->redirectM3Url($subject);
    }

    private function redirectM3Url($subject) {
        $username = $subject->getRequest()->getPost('login')['username'];
        $this->dataPersistor->clear('login_username');
        $this->dataPersistor->set('login_username', $username);
        $redirectionUrl = $this->authClient->getOauthLink();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectionUrl);
        return $resultRedirect;
    }
}
