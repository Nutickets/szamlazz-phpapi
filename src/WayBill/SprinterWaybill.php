<?php

namespace SzamlaAgent\WayBill;

use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class SprinterWaybill extends Waybill
{
    protected $id;
    protected $senderId;
    protected $shipmentZip;
    protected $packetNumber;
    protected $barcodePostfix;
    protected $shippingTime;

    public function __construct($destination = '', $barcode = '', $comment = '')
    {
        parent::__construct($destination, self::WAYBILL_TYPE_SPRINTER, $barcode, $comment);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'packetNumber':
                    SzamlaAgentUtil::checkIntField($field, $value, false, __CLASS__);
                    break;
                case 'id':
                case 'senderId':
                case 'shipmentZip':
                case 'barcodePostfix':
                case 'shippingTime':
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

        $data['sprinter'] = [];
        if (SzamlaAgentUtil::isNotBlank($this->getId())) {
            $data['sprinter']['azonosito'] = $this->getId();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getSenderId())) {
            $data['sprinter']['feladokod'] = $this->getSenderId();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getShipmentZip())) {
            $data['sprinter']['iranykod'] = $this->getShipmentZip();
        }
        if (SzamlaAgentUtil::isNotNull($this->getPacketNumber())) {
            $data['sprinter']['csomagszam'] = $this->getPacketNumber();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getBarcodePostfix())) {
            $data['sprinter']['vonalkodPostfix'] = $this->getBarcodePostfix();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getShippingTime())) {
            $data['sprinter']['szallitasiIdo'] = $this->getShippingTime();
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

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function setSenderId($senderId): static
    {
        $this->senderId = $senderId;

        return $this;
    }

    public function getShipmentZip()
    {
        return $this->shipmentZip;
    }

    public function setShipmentZip($shipmentZip): static
    {
        $this->shipmentZip = $shipmentZip;

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

    public function getBarcodePostfix()
    {
        return $this->barcodePostfix;
    }

    public function setBarcodePostfix($barcodePostfix): static
    {
        $this->barcodePostfix = $barcodePostfix;

        return $this;
    }

    public function getShippingTime()
    {
        return $this->shippingTime;
    }

    public function setShippingTime($shippingTime): static
    {
        $this->shippingTime = $shippingTime;

        return $this;
    }
}
