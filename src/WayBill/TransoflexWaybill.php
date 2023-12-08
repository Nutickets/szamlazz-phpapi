<?php

namespace SzamlaAgent\WayBill;

use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class TransoflexWaybill extends Waybill
{
    protected $id;
    protected $shippingId;
    protected $packetNumber;
    protected $countryCode;
    protected $zip;
    protected $service;

    public function __construct($destination = '', $barcode = '', $comment = '')
    {
        parent::__construct($destination, self::WAYBILL_TYPE_TRANSOFLEX, $barcode, $comment);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'packetNumber':
                    SzamlaAgentUtil::checkIntField($field, $value, false, __CLASS__);
                    break;
                case 'id':
                case 'shippingId':
                case 'countryCode':
                case 'zip':
                case 'service':
                    SzamlaAgentUtil::checkStrField($field, $value, false, __CLASS__);
                    break;
            }
        }
        return $value;
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        $this->checkFields(get_class());
        $data = parent::buildXmlData($request);

        $data['tof'] = [];
        if (SzamlaAgentUtil::isNotBlank($this->getId())) {
            $data['tof']['azonosito'] = $this->getId();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getShippingId())) {
            $data['tof']['shippingID'] = $this->getShippingId();
        }
        if (SzamlaAgentUtil::isNotNull($this->getPacketNumber())) {
            $data['tof']['csomagszam'] = $this->getPacketNumber();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getCountryCode())) {
            $data['tof']['countryCode'] = $this->getCountryCode();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getZip())) {
            $data['tof']['zip'] = $this->getZip();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getService())) {
            $data['tof']['service'] = $this->getService();
        }

        return $data;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getShippingId()
    {
        return $this->shippingId;
    }

    public function setShippingId($shippingId): static
    {
        $this->shippingId = $shippingId;

        return $this;
    }

    public function getPacketNumber()
    {
        return $this->packetNumber;
    }

    public function setPacketNumber($packetNumber): static
    {
        $this->packetNumber = $packetNumber;

        return $this;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function setCountryCode($countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function setZip($zip): static
    {
        $this->zip = $zip;

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
}
