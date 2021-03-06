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

namespace LeanSwift\Login\Block\Backend\Roles\Ion;

use LeanSwift\EconnectBase\Helper\Data as BaseDataHelper;
use LeanSwift\Login\Helper\Constant;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
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
    public $buttonLabel = 'Import';

    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var BaseDataHelper
     */
    protected $baseDataHelper;

    /**
     * Initial constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param BaseDataHelper $baseDataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        BaseDataHelper $baseDataHelper,
        array $data = []
    ) {
        $this->_request = $request;
        $this->baseDataHelper = $baseDataHelper;
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
        $type = Constant::TYPE;
        $website = $this->_request->getParam('website');
        $redirectUrl = $this->getRedirectUrl() . "website/$website";
        $message = $this->getMessage();
        $timeZone = $this->baseDataHelper->getTimeZone();
        $lastUpdatedTime = $this->baseDataHelper->getLastUpdatedAtHistory($type) ?? '';
        $html = $this->getLayout()
            ->createBlock(Button::class)
            ->setType('button')
            ->setLabel($this->buttonLabel)
            ->setOnClick("javascript:check('" . $redirectUrl . "','" . $message . "'); return false;")
            ->toHtml();
        $html .= '<p style="display: inline;">';
        $html .= 'Last synced: ';
        if ($lastUpdatedTime) {
            $html .= $lastUpdatedTime . ' [' . $timeZone . ']';
        }
        $html .= '</p>';
        return $html;
    }

    /**
     * Return redirect url for button
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl(self::ION_CONFIG_URL);
    }

    /**
     * Return confirmation popup message
     *
     * @return string
     */
    public function getMessage()
    {
        return "Are you sure you want to import user roles data from M3?";
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
