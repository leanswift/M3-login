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

namespace LeanSwift\Login\Model\Subscriber;

use Exception;
use LeanSwift\EconnectBase\Api\MessageInterface;
use LeanSwift\EconnectBase\Api\SubscriberInterface;
use LeanSwift\Login\Helper\Constant;
use LeanSwift\Login\Helper\Xpath;

/**
 * Class UserRoles
 * @package LeanSwift\Login\Model\Subscriber
 */
class UserRoles extends IonAbstractModel implements SubscriberInterface
{
    /**
     * Process the message from Queue
     *
     * @param MessageInterface $message
     *
     * @return bool|mixed
     */
    public function processMessage(MessageInterface $message)
    {
        $consumerMessage = $this->erpApiHelper->utf8_for_xml(base64_decode($message->getMessage()));

        try {
            $this->_xmlParser->loadXML($consumerMessage);
            //Parsing XML Data to Array
            $parsedXML = $this->_xmlParser->xmlToArray();

            if ($parsedXML) {
                $rolesQueue = [Constant::SyncLSUserRoles];
                $rolesQueueData = $this->getDataFromParsedXML($rolesQueue, $parsedXML);
                $dataAreaSection = $this->getDataArea($rolesQueueData);

                $bodType = [Constant::Sync];
                $bodData = $this->getDataFromParsedXML($bodType, $dataAreaSection);
                $actionCode = $this->getActionCode($bodData);
                if ($actionCode == 'Delete') {
                    return false;
                }
                $dataArea = [Constant::LSUserRoles];
                $bodName = $this->getBodName($dataArea, $dataAreaSection);
                $userResponseData = $this->getDataFromParsedXML($dataArea, $dataAreaSection);
                $bodDetails = $this->getBodData($rolesQueueData, $userResponseData);
                $bodId = ($bodDetails && array_key_exists(Constant::BOD_ID, $bodDetails))
                    ? $bodDetails[Constant::BOD_ID] : null;
                $variationId = $this->dataParser($userResponseData, Xpath::UserRoles_VariationId);
                $this->logger->writeLog($bodName . ' - ' . $bodId . ' - ' . $variationId);
                //Prepare Data
                $username = $userResponseData['LSUserRolesHeader']['DocumentID']['ID']['_value'];
                $userData = $this->_prepareData($userResponseData);
                $this->erpApiHelper->updateuser($username, $userData);
            }
        } catch (Exception $e) {
            $this->logger->writeLog($e->getMessage());
            return false;
        }
    }

    /**
     * Prepares data from UserRoles BOD
     *
     * @param $userRoleData
     * @return mixed
     */
    public function _prepareData($userRoleData)
    {
        $data = $userRoleData['LSUserRoleList'];
        return $this->getSerializerObject()->serialize($data);
    }
}
