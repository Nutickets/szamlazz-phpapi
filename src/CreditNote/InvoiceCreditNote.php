<?php

namespace SzamlaAgent\CreditNote;

use SzamlaAgent\Document\Document;
use SzamlaAgent\SzamlaAgentUtil;

class InvoiceCreditNote extends CreditNote
{
    protected $date;

    protected $requiredFields = ['date', 'paymentMode', 'amount'];

    public function __construct($date, $amount, $paymentMode = Document::PAYMENT_METHOD_TRANSFER, $description = '')
    {
        parent::__construct($paymentMode, $amount, $description);
        $this->setDate($date);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'date':
                    SzamlaAgentUtil::checkDateField($field, $value, $required, __CLASS__);
                    break;
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

        if (SzamlaAgentUtil::isNotBlank($this->getDate())) {
            $data['datum']  = $this->getDate();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getPaymentMode())) {
            $data['jogcim'] = $this->getPaymentMode();
        }
        if (SzamlaAgentUtil::isNotNull($this->getAmount())) {
            $data['osszeg'] = SzamlaAgentUtil::doubleFormat($this->getAmount());
        }
        if (SzamlaAgentUtil::isNotBlank($this->getDescription())) {
            $data['leiras'] = $this->getDescription();
        }

        return $data;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): static
    {
        $this->date = $date;

        return $this;
    }
}
