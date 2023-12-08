<?php

namespace SzamlaAgent\WayBill;

use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class MPLWaybill extends Waybill
{
    protected $buyerCode;
    protected $barcode;
    protected $weight;
    protected $service;
    protected $insuredValue;
    protected $requiredFields = ['buyerCode', 'barcode', 'weight'];

    public function __construct($destination = '', $barcode = '', $comment = '')
    {
        parent::__construct($destination, self::WAYBILL_TYPE_MPL, $barcode, $comment);
    }

    protected function getRequiredFields()
    {
        return $this->requiredFields;
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'insuredValue':
                    SzamlaAgentUtil::checkDoubleField($field, $value, $required, __CLASS__);
                    break;
                case 'buyerCode':
                case 'weight':
                case 'service':
                case 'shippingTime':
                    SzamlaAgentUtil::checkStrField($field, $value, $required, __CLASS__);
                    break;
            }
        }
        return $value;
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        $this->checkFields(get_class());
        $data = parent::buildXmlData($request);

        $data['mpl'] = [];
        $data['mpl']['vevokod'] = $this->getBuyerCode();
        $data['mpl']['vonalkod'] = $this->getBarcode();
        $data['mpl']['tomeg'] = $this->getWeight();

        if (SzamlaAgentUtil::isNotBlank($this->getService())) {
            $data['mpl']['kulonszolgaltatasok'] = $this->getService();
        }

        if (SzamlaAgentUtil::isNotNull($this->getInsuredValue())) {
            $data['mpl']['erteknyilvanitas'] = SzamlaAgentUtil::doubleFormat($this->getInsuredValue());
        }

        return $data;
    }

    public function getBuyerCode()
    {
        return $this->buyerCode;
    }

    public function setBuyerCode($buyerCode): static
    {
        $this->buyerCode = $buyerCode;

        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getService()
    {
        return $this->service;
    }

    public function setService($service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getInsuredValue()
    {
        return $this->insuredValue;
    }

    public function setInsuredValue($insuredValue): static
    {
        $this->insuredValue = (float)$insuredValue;

        return $this;
    }
}
