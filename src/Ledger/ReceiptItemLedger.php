<?php

namespace SzamlaAgent\Ledger;

use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class ReceiptItemLedger extends ItemLedger
{
    public function __construct($revenueLedgerNumber = '', $vatLedgerNumber = '')
    {
        parent::__construct($revenueLedgerNumber, $vatLedgerNumber);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'revenueLedgerNumber':
                case 'vatLedgerNumber':
                    SzamlaAgentUtil::checkStrField($field, $value, false, __CLASS__);
                    break;
            }
        }
        return $value;
    }

    protected function checkFields()
    {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            $this->checkField($field, $value);
        }
    }

    public function buildXmlData()
    {
        $data = [];
        $this->checkFields();

        if (SzamlaAgentUtil::isNotBlank($this->getRevenueLedgerNumber())) {
            $data['arbevetel'] = $this->getRevenueLedgerNumber();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getVatLedgerNumber())) {
            $data['afa'] = $this->getVatLedgerNumber();
        }

        return $data;
    }
}
