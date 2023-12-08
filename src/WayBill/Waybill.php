<?php

namespace SzamlaAgent\WayBill;

use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;


class Waybill
{
    const WAYBILL_TYPE_TRANSOFLEX = 'Transoflex';
    const WAYBILL_TYPE_SPRINTER = 'Sprinter';
    const WAYBILL_TYPE_PPP = 'PPP';
    const WAYBILL_TYPE_MPL = 'MPL';

    protected $destination;
    protected $parcel;
    protected $barcode;
    protected $comment;

    protected function __construct($destination = '', $parcel = '', $barcode = '', $comment = '')
    {
        $this->setDestination($destination);
        $this->setParcel($parcel);
        $this->setBarcode($barcode);
        $this->setComment($comment);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'destination':
                case 'parcel':
                case 'barcode':
                case 'comment':
                    SzamlaAgentUtil::checkStrField($field, $value, false, __CLASS__);
                    break;
            }
        }
        return $value;
    }

    protected function checkFields($entity = null)
    {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            if (get_class() == $entity) {
                self::checkField($field, $value);
            } else {
                $this::checkField($field, $value);
            }
        }
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        $data = [];
        self::checkFields(get_class());

        if (SzamlaAgentUtil::isNotBlank($this->getDestination())) $data['uticel'] = $this->getDestination();
        if (SzamlaAgentUtil::isNotBlank($this->getParcel()))      $data['futarSzolgalat'] = $this->getParcel();
        if (SzamlaAgentUtil::isNotBlank($this->getBarcode()))     $data['vonalkod'] = $this->getBarcode();
        if (SzamlaAgentUtil::isNotBlank($this->getComment()))     $data['megjegyzes'] = $this->getComment();

        return $data;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setDestination($destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getParcel()
    {
        return $this->parcel;
    }

    public function setParcel($parcel): static
    {
        $this->parcel = $parcel;

        return $this;
    }

    public function getBarcode()
    {
        return $this->barcode;
    }

    public function setBarcode($barcode): static
    {
        $this->barcode = $barcode;

        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
