<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Document;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class ReceiptHeader extends DocumentHeader
{
    protected $receiptNumber;
    protected $callId;
    protected $prefix = '';
    protected $paymentMethod;
    protected $currency;
    protected $exchangeBank;
    protected $exchangeRate;
    protected $comment;
    protected $pdfTemplate;
    protected $buyerLedgerId;
    protected $requiredFields;

    public function __construct($receiptNumber = '')
    {
        $this->setReceipt(true);
        $this->setReceiptNumber($receiptNumber);
        $this->setPaymentMethod(Document::PAYMENT_METHOD_CASH);
        $this->setCurrency(Document::getDefaultCurrency());
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'exchangeRate':
                    SzamlaAgentUtil::checkDoubleField($field, $value, $required, __CLASS__);
                    break;
                case 'receiptNumber':
                case 'callId':
                case 'prefix':
                case 'paymentMethod':
                case 'currency':
                case 'exchangeBank':
                case 'comment':
                case 'pdfTemplate':
                case 'buyerLedgerId':
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

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        if (empty($request)) {
            throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_NOT_AVAILABLE);
        }
        $requireFields = ['receiptNumber'];
        switch ($request->getXmlName()) {
            case $request::XML_SCHEMA_CREATE_RECEIPT:
                $requireFields = ['prefix', 'paymentMethod', 'currency'];
                $data = $this->buildFieldsData($request, [
                    'hivasAzonosito', 'elotag', 'fizmod', 'penznem', 'devizabank', 'devizaarf', 'megjegyzes', 'pdfSablon', 'fokonyvVevo'
                ]);
                break;
            case $request::XML_SCHEMA_CREATE_REVERSE_RECEIPT:
                $data = $this->buildFieldsData($request, ['nyugtaszam', 'pdfSablon', 'hivasAzonosito']);
                break;
            case $request::XML_SCHEMA_GET_RECEIPT:
                $data = $this->buildFieldsData($request, ['nyugtaszam', 'pdfSablon']);
                break;
            case $request::XML_SCHEMA_SEND_RECEIPT:
                $data = $this->buildFieldsData($request, ['nyugtaszam']);
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$request->getXmlName()}");
        }
        $this->setRequiredFields($requireFields);
        $this->checkFields();

        return $data;
    }

    private function buildFieldsData(SzamlaAgentRequest $request, array $fields)
    {
        $data = [];

        if (empty($request) || !empty($field)) {
            throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_NOT_AVAILABLE);
        }

        foreach ($fields as $key) {
            $value = match ($key) {
                'hivasAzonosito' => (SzamlaAgentUtil::isNotBlank($this->getCallId())) ? $this->getCallId() : null,
                'elotag' => $this->getPrefix(),
                'fizmod' => $this->getPaymentMethod(),
                'penznem' => $this->getCurrency(),
                'devizabank' => (SzamlaAgentUtil::isNotBlank($this->getExchangeBank())) ? $this->getExchangeBank() : null,
                'devizaarf' => (SzamlaAgentUtil::isNotNull($this->getExchangeRate())) ? SzamlaAgentUtil::doubleFormat($this->getExchangeRate()) : null,
                'megjegyzes' => (SzamlaAgentUtil::isNotBlank($this->getComment())) ? $this->getComment() : null,
                'pdfSablon' => (SzamlaAgentUtil::isNotBlank($this->getPdfTemplate())) ? $this->getPdfTemplate() : null,
                'fokonyvVevo' => (SzamlaAgentUtil::isNotBlank($this->getBuyerLedgerId())) ? $this->getBuyerLedgerId() : null,
                'nyugtaszam' => $this->getReceiptNumber(),
                default => throw new SzamlaAgentException(SzamlaAgentException::XML_KEY_NOT_EXISTS . ": {$key}"),
            };

            if (isset($value)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod($paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix($prefix): static
    {
        $this->prefix = $prefix;

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

    public function getExchangeBank()
    {
        return $this->exchangeBank;
    }

    public function setExchangeBank($exchangeBank): static
    {
        $this->exchangeBank = $exchangeBank;

        return $this;
    }

    public function getExchangeRate()
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate($exchangeRate): static
    {
        $this->exchangeRate = (float)$exchangeRate;

        return $this;
    }

    public function getReceiptNumber()
    {
        return $this->receiptNumber;
    }

    public function setReceiptNumber($receiptNumber): static
    {
        $this->receiptNumber = $receiptNumber;

        return $this;
    }

    protected function getRequiredFields()
    {
        return $this->requiredFields;
    }

    protected function setRequiredFields(array $requiredFields): static
    {
        $this->requiredFields = $requiredFields;

        return $this;
    }

    public function getCallId()
    {
        return $this->callId;
    }

    public function setCallId($callId): static
    {
        $this->callId = $callId;

        return $this;
    }

    public function getPdfTemplate()
    {
        return $this->pdfTemplate;
    }

    public function setPdfTemplate($pdfTemplate): static
    {
        $this->pdfTemplate = $pdfTemplate;

        return $this;
    }

    public function getBuyerLedgerId()
    {
        return $this->buyerLedgerId;
    }

    public function setBuyerLedgerId($buyerLedgerId): static
    {
        $this->buyerLedgerId = $buyerLedgerId;

        return $this;
    }
}
