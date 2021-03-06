<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
namespace Infomodus\Upsap\Helper;

use Magento\Framework\App\ObjectManager;

class Ups extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $_context;
    public $_objectManager;
    public $_conf;
    public $_registry;
    public $_checkoutSession;
    public $storeId;
    public $negotiated_rates;
    public $rates_tax;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Infomodus\Upsap\Helper\Config $config,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_registry = $registry;
        parent::__construct($context);
        $this->_context = $context;
        $this->_objectManager = $objectManager;
        $this->_conf = $config;
        $this->_checkoutSession = $checkoutSession;

    }

    public function getShipRate($lbl, $nr=0)
    {
        return $lbl->getShipRate($nr);
    }

    public
    function setParams($lbl, $params, $packages)
    {
        if ($lbl === NULL) {
            $lbl = $this->_objectManager->get('Infomodus\Upsap\Model\Ups');
        }
        $lbl->_handy = $this;
        $lbl->packages = $packages;

        $lbl->shiptoStateProvinceCode = $this->_conf->escapeXML($params['shiptostateprovincecode']);
        $lbl->shiptoCity = $this->_conf->escapeXML($params['shiptocity']);
        $lbl->shiptoPostalCode = $this->_conf->escapeXML($params['shiptopostalcode']);
        $lbl->shiptoCountryCode = $this->_conf->escapeXML($params['shiptocountrycode']);
        $lbl->shipmentIndicationType = $this->_conf->getStoreConfig('carriers/upsap/type', $this->storeId);

        if($lbl->shiptoCountryCode == 'US' && $lbl->shiptoStateProvinceCode == 'PR'){
            $lbl->shiptoCountryCode = 'PR';
            $lbl->shiptoStateProvinceCode = '';
        }

        if ($lbl->shiptoCountryCode == 'ES') {
            if (in_array(substr($lbl->shiptoPostalCode, 0, 2), ['35', '38'])) {
                $lbl->shiptoCountryCode = 'CI';
                $lbl->shiptoStateProvinceCode = '';
            }
        }

        $lbl->AccessLicenseNumber = $this->_conf->getStoreConfig('carriers/upsap/accesslicensenumber', $this->storeId);
        $lbl->UserID = $this->_conf->getStoreConfig('carriers/upsap/userid', $this->storeId);
        $lbl->Password = $this->_conf->getStoreConfig('carriers/upsap/password', $this->storeId);
        $lbl->shipperNumber = $this->_conf->getStoreConfig('carriers/upsap/shippernumber', $this->storeId);

        $lbl->residentialAddress = 0;

        if ($this->_conf->getStoreConfig('carriers/upsap/dest_type', $this->storeId) == 3) {
            $lbl->residentialAddress = strlen($params['shiptocompany']) > 0 ? 1 : 2;
        } else if ($this->_conf->getStoreConfig('carriers/upsap/dest_type', $this->storeId) == 1) {
            $lbl->residentialAddress = 1;
        } else if ($this->_conf->getStoreConfig('carriers/upsap/dest_type', $this->storeId) == 2) {
            $lbl->residentialAddress = 2;
        }

        $lbl->weightUnits = $this->_conf->getStoreConfig('carriers/upsap/unit_of_measure', $this->storeId);

        $lbl->insured = 0;

        if($this->_conf->isModuleOutputEnabled("Infomodus_Upslabel")) {
            $params['shipper_no'] = $this->_conf->getStoreConfig('upslabel/shipping/defaultshipper', $this->storeId);
            $address = ObjectManager::getInstance()->get(\Infomodus\Upslabel\Model\Config\Defaultaddress::class)->getAddressesById($params['shipper_no']);

            $lbl->shipperCity = $this->_conf->escapeXML($address->getCity());
            $lbl->shipperStateProvinceCode = $this->_conf->escapeXML($address->getProvinceCode());
            $lbl->shipperPostalCode = $this->_conf->escapeXML($address->getPostalCode());
            $lbl->shipperCountryCode = $this->_conf->escapeXML($address->getCountry());

            $params['shipfrom_no'] = $this->_conf->getStoreConfig('upslabel/shipping/defaultshipfrom', $this->storeId);
            $address = ObjectManager::getInstance()->get(\Infomodus\Upslabel\Model\Config\Defaultaddress::class)->getAddressesById($params['shipfrom_no']);

            $lbl->shipfromCity = $this->_conf->escapeXML($address->getCity());
            $lbl->shipfromStateProvinceCode = $this->_conf->escapeXML($address->getProvinceCode());
            $lbl->shipfromPostalCode = $this->_conf->escapeXML($address->getPostalCode());
            $lbl->shipfromCountryCode = $this->_conf->escapeXML($address->getCountry());

            $lbl->unitOfMeasurement = $this->_conf->getStoreConfig('upslabel/weightdimension/unitofmeasurement', $this->storeId);

            $lbl->insured = $this->_conf->getStoreConfig('upslabel/ratepayment/insured_automaticaly', $this->storeId);

            if ($this->_conf->getStoreConfig('upslabel/quantum/adult', $this->storeId) != 1
                    ||
                    strpos($this->_conf->getStoreConfig('upslabel/quantum/adult_allow_country', $this->storeId), $lbl->shiptoCountryCode) !== false
            ) {
                $lbl->adult = $this->_conf->escapeXML($this->_conf->getStoreConfig('upslabel/quantum/adult', $this->storeId));
            }
        } else {
            $countryCode = $this->_conf->getStoreConfig('shipping/origin/country_id', $this->storeId);
            $stateCode = $this->_conf->getStoreConfig('shipping/origin/region_id', $this->storeId);
            $states = $this->_objectManager->get('Magento\Directory\Model\Country')
                ->loadByCode($countryCode)->getRegions()->getData();
            if (count($states) > 0) {
                foreach ($states as $state) {
                    if ($state['default_name'] == $stateCode) {
                        $stateCode = $state['code'];
                        break;
                    }

                    if ($state['code'] == $stateCode) {
                        $stateCode = $state['code'];
                        break;
                    }

                    if ($state['region_id'] == $stateCode) {
                        $stateCode = $state['code'];
                        break;
                    }
                }
            }

            $lbl->shipperCity = $this->_conf->escapeXML($this->_conf->getStoreConfig('shipping/origin/city', $this->storeId));
            $lbl->shipperStateProvinceCode = $this->_conf->escapeXML($stateCode);
            $lbl->shipperPostalCode = $this->_conf->escapeXML($this->_conf->getStoreConfig('shipping/origin/postcode', $this->storeId));
            $lbl->shipperCountryCode = $this->_conf->escapeXML($countryCode);

            $lbl->shipfromStateProvinceCode = $this->_conf->escapeXML($stateCode);
            $lbl->shipfromPostalCode = $this->_conf->escapeXML($this->_conf->getStoreConfig('shipping/origin/postcode', $this->storeId));
            $lbl->shipfromCountryCode = $this->_conf->escapeXML($countryCode);
        }

        if($lbl->shipperCountryCode == 'US' && $lbl->shipperStateProvinceCode == 'PR'){
            $lbl->shipperCountryCode = 'PR';
            $lbl->shipperStateProvinceCode = '';
        }

        if ($lbl->shipperCountryCode == 'ES') {
            if (in_array(substr($lbl->shipperPostalCode, 0, 2), ['35', '38'])) {
                $lbl->shipperCountryCode = 'CI';
                $lbl->shipperStateProvinceCode = '';
            }
        }

        if($lbl->shipfromCountryCode == 'US' && $lbl->shipfromStateProvinceCode == 'PR'){
            $lbl->shipfromCountryCode = 'PR';
            $lbl->shipfromStateProvinceCode = '';
        }

        if ($lbl->shipfromCountryCode == 'ES') {
            if (in_array(substr($lbl->shipfromPostalCode, 0, 2), ['35', '38'])) {
                $lbl->shipfromCountryCode = 'CI';
                $lbl->shipfromStateProvinceCode = '';
            }
        }

        return $lbl;
    }
}
