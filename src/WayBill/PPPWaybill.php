<?php

namespace SzamlaAgent\WayBill;

use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class PPPWaybill extends Waybill
{
    protected $barcodePrefix;
    protected $barcodePostfix;

    public function __construct($destination = '', $barcode = '', $comment = '')
    {
        parent::__construct($destination, self::WAYBILL_TYPE_PPP, $barcode, $comment);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'barcodePrefix':
                case 'barcodePostfix':
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

        $data['ppp'] = [];
        if (SzamlaAgentUtil::isNotBlank($this->getBarcodePrefix())) {
            $data['ppp']['vonalkodPrefix']  = $this->getBarcodePrefix();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getBarcodePostfix())) {
            $data['ppp']['vonalkodPostfix'] = $this->getBarcodePostfix();
        }

        return $data;
    }

    public function getBarcodePrefix()
    {
        return $this->barcodePrefix;
    }

    public function setBarcodePrefix($barcodePrefix): static
    {
        $this->barcodePrefix = $barcodePrefix;

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
}
