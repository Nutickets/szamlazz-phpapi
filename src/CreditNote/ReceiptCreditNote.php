<?php

namespace SzamlaAgent\CreditNote;

use SzamlaAgent\Document\Document;
use SzamlaAgent\SzamlaAgentUtil;

class ReceiptCreditNote extends CreditNote
{
    protected $paymentMode;

    protected $amount;

    protected $description = '';

    public function __construct($paymentMode = Document::PAYMENT_METHOD_CASH, $amount = 0.0, $description = '')
    {
        parent::__construct($paymentMode, $amount, $description);
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
                case 'amount':
                    SzamlaAgentUtil::checkDoubleField($field, $value, $required, __CLASS__);
                    break;
                case 'paymentMode':
                case 'description':
                    SzamlaAgentUtil::checkStrField($field, $value, $required, __CLASS__);
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

        if (SzamlaAgentUtil::isNotBlank($this->getPaymentMode())) {
            $data['fizetoeszkoz'] = $this->getPaymentMode();
        }
        if (SzamlaAgentUtil::isNotNull($this->getAmount())) {
            $data['osszeg'] = SzamlaAgentUtil::doubleFormat($this->getAmount());
        }
        if (SzamlaAgentUtil::isNotBlank($this->getDescription())) {
            $data['leiras'] = $this->getDescription();
        }

        return $data;
    }
}
