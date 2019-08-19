<?php
/**
 * Copyright © 2015 Infomodus. All rights reserved.
 */

namespace Infomodus\Upsap\Controller\Adminhtml\Index;

class SetSessionAddressAP extends \Infomodus\Upsap\Controller\Adminhtml\Index
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $address = $this->getRequest()->getParams();
        $session = $this->_checkoutSession;
        if (isset($address['upsap_addLine1'])) {
            $session->setUpsapAddLine1($address['upsap_addLine1']);

            if (isset($address['upsap_addLine2'])) {
                $session->setUpsapAddLine2($address['upsap_addLine2']);
            }

            if (isset($address['upsap_addLine3'])) {
                $session->setUpsapAddLine3($address['upsap_addLine3']);
            }

            if (isset($address['upsap_city'])) {
                $session->setUpsapCity($address['upsap_city']);
            }

            if (isset($address['upsap_country'])) {
                $session->setUpsapCountry($address['upsap_country']);
            }

            if (isset($address['upsap_fax'])) {
                $session->setUpsapFax($address['upsap_fax']);
            }

            if (isset($address['upsap_state'])) {
                $session->setUpsapState($address['upsap_state']);
            }

            if (isset($address['upsap_postal'])) {
                $session->setUpsapPostal($address['upsap_postal']);
            }

            if (isset($address['upsap_appuId'])) {
                $session->setUpsapAppuId($address['upsap_appuId']);
            }

            if (isset($address['upsap_name'])) {
                $session->setUpsapName($address['upsap_name']);
            }
        }

        return $result->setData($address);
    }
}