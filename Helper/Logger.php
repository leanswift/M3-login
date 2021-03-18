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
 *   @copyright   Copyright (c) 2021 LeanSwift Inc. (http://www.leanswift.com)
 *   @license     https://www.leanswift.com/end-user-licensing-agreement
 *
 */

namespace LeanSwift\Login\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Logger
 * @package LeanSwift\Login\Helper
 */
class Logger extends AbstractHelper
{
    protected $isLogEnabled;
    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * Logger constructor.
     * @param Context $context
     * @param \Monolog\Logger $logger
     */
    public function __construct(
        Context $context,
        \Monolog\Logger $logger
    ){
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param $message
     */
    public function writeLog($message)
    {
        if($this->isLogEnabled == '')
        {
            $this->isLogEnabled = $this->scopeConfig->getValue(Constant::LOGGER_ENABLE_PATH);
        }
        if($this->isLogEnabled == 1)
        {
            $this->logger->info($message);
        }
    }
}
