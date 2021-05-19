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

namespace LeanSwift\Login\Model\Subscriber;

use LeanSwift\EconnectBase\Model\Parser as BaseParser;
use LeanSwift\Login\Helper\Constant;
use LeanSwift\Login\Helper\Erpapi;
use LeanSwift\Login\Helper\Logger;
use LeanSwift\Login\Helper\Xpath;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Xml\Parser;

/**
 * Class IonAbstractModel
 * @package LeanSwift\Login\Model\Subscriber
 */
abstract class IonAbstractModel extends AbstractExtensibleModel
{
    /**
     * @var XMLParser
     */
    protected $_xmlParser;
    /**
     * @var BaseParser
     */
    protected $baseParser;
    /**
     * @var Erpapi
     */
    protected $erpApiHelper;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * IonAbstractModel constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param BaseParser $baseParser
     * @param Parser $parser
     * @param Erpapi $erpApiHelper
     * @param Logger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        BaseParser $baseParser,
        Parser $parser,
        Erpapi $erpApiHelper,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->baseParser = $baseParser;
        $this->_xmlParser = $parser;
        $this->erpApiHelper = $erpApiHelper;
        $this->logger = $logger;
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $resource, $resourceCollection, $data);
    }

    public function getSerializerObject()
    {
        return $this->erpApiHelper->getSerializerObject();
    }

    /**
     * @param $xmlArray
     * @param $queueData
     *
     * @return mixed
     */
    public function getBodData($xmlArray, $queueData)
    {
        $bodDataArray = [Constant::BOD_ID => Xpath::BOD_ID_PATH, Constant::BOD_Timestamp => Xpath::CREATION_DATE_TIME];
        foreach ($bodDataArray as $key => $value) {
            $addValue = (array_key_exists('_value', $xmlArray)) ? $xmlArray['_value'] : $xmlArray;
            $queueData[$key] = $this->dataParser($addValue, $value);
        }

        return $queueData;
    }

    /**
     * Array Parser is a function to get the end value by passing the xpath and array source.
     * Don't change/delete this function which act as a library to parse the ION BOD.
     *
     * @param      $response
     * @param      $path
     * @param null $pathKey
     * @param null $pathValue
     *
     * @return array|mixed|null|string
     */
    public function dataParser($response, $path, $pathKey = null, $pathValue = null)
    {
        return $this->baseParser->dataParser($response, $path, $pathKey, $pathValue);
    }

    /**
     * @param $data
     *
     * @return mixed|null
     */
    public function getActionCode($data)
    {
        return $this->dataParser($data, Xpath::ACTION_PATH);
    }

    /**
     * @param $data
     *
     * @return mixed|null
     */
    public function getDataArea($data)
    {
        return $this->dataParser($data, Xpath::DATA_AREA_PATH);
    }

    /**
     * @param $data Data to filter from the whole XML Array
     * @param $xmlData
     *
     * @return array
     */
    public function getDataFromParsedXML($data, $xmlData)
    {
        $result = [];
        if ($data && is_array($xmlData)) {
            foreach ($data as $key) {
                if (array_key_exists($key, $xmlData)) {
                    $result = $xmlData[$key];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param $data
     * @param $xmlData
     *
     * @return mixed
     */
    public function getBodName($data, $xmlData)
    {
        $bodName = '';
        if ($data && is_array($xmlData)) {
            foreach ($xmlData as $key => $value) {
                if (in_array($key, $data)) {
                    $bodName = $key;
                    break;
                }
            }
        }

        return $bodName;
    }
}
