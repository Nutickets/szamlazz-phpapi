<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class ReverseReceiptHeader extends ReceiptHeader
{
    protected $requiredFields = ['receiptNumber'];

    public function __construct($receiptNumber = '')
    {
        parent::__construct($receiptNumber);
        $this->setReverseReceipt(true);
    }

    public function checkField($field, $value)
    {
        if (property_exists(get_parent_class($this), $field) || property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'receiptNumber':
                case 'pdfTemplate':
                case 'callId':
                    SzamlaAgentUtil::checkStrField($field, $value, $required, __CLASS__);
                    break;
            }
        }

        return $value;
    }
}
