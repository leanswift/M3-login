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

namespace LeanSwift\Login\Block\Backend\Roles\Ion;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Request\Http as RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Initial
 *
 * @package LeanSwift\Login\Block\Roles\Ion
 */
class Initial extends Field
{

    /**
     * Controller path
     */
    const ION_CONFIG_URL = "lslogin/ion/import";

    /**
     * @var string
     */
    public $buttonLabel = 'Import Roles';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * Initial constructor.
     *
     * @param Context          $context
     * @param RequestInterface $request
     * @param array            $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        array $data = []
    ) {
        $this->_request = $request;
        parent::__construct($context, $data);
        $this->setTemplate('system/config/button.phtml');
    }

    /**
     * @param string $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->buttonLabel = $buttonLabel;
        return $this;
    }

    /**
     * Generate button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $website = $this->_request->getParam('website');
        $redirectUrl = $this->getRedirectUrl() . "website/$website";
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'      => 'Import button',
                'label'   => __('Import Roles'),
                'onclick' => 'window.location="' . $redirectUrl . '";',
            ]
        );

        return $button->toHtml();
    }

    /**
     * Return redirect url for button
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        $url = $this->getUrl(self::ION_CONFIG_URL);
        return $url;
    }

    /**
     * Adds button in configuration page to load initial Customer data
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
