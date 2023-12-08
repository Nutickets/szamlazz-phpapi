<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class InvoiceHeader extends DocumentHeader
{
    protected $invoiceNumber;
    protected $invoiceType;
    protected $issueDate;
    protected $paymentMethod;
    protected $currency;
    protected $language;
    protected $fulfillment;
    protected $paymentDue;
    protected $prefix = '';
    protected $extraLogo;
    protected $correctionToPay;
    protected $correctiveNumber = '';
    protected $comment;
    protected $exchangeBank;
    protected $exchangeRate;
    protected $orderNumber = '';
    protected $proformaNumber = '';
    protected $paid = false;
    protected $profitVat = false;
    protected $invoiceTemplate = Invoice::INVOICE_TEMPLATE_DEFAULT;
    protected $prePaymentInvoiceNumber;
    protected $previewPdf = false;
    protected $euVat = false;
    protected $requiredFields = [];

    public function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE)
    {
        if (!empty($type)) {
            $this->setDefaultData($type);
        }
    }

    public function setDefaultData($type): static
    {
        $this->setInvoice(true);
        $this->setInvoiceType($type);
        $this->setIssueDate(SzamlaAgentUtil::getTodayStr());
        $this->setPaymentMethod(Document::PAYMENT_METHOD_TRANSFER);
        $this->setCurrency(Document::getDefaultCurrency());
        $this->setLanguage(Document::getDefaultLanguage());
        $this->setFulfillment(SzamlaAgentUtil::getTodayStr());
        $this->setPaymentDue(SzamlaAgentUtil::addDaysToDate(SzamlaAgentUtil::DEFAULT_ADDED_DAYS));

        return $this;
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'issueDate':
                case 'fulfillment':
                case 'paymentDue':
                    SzamlaAgentUtil::checkDateField($field, $value, $required, __CLASS__);
                    break;
                case 'exchangeRate':
                case 'correctionToPay':
                    SzamlaAgentUtil::checkDoubleField($field, $value, $required, __CLASS__);
                    break;
                case 'proforma':
                case 'deliveryNote':
                case 'prePayment':
                case 'final':
                case 'reverse':
                case 'paid':
                case 'profitVat':
                case 'corrective':
                case 'previewPdf':
                case 'euVat':
                    SzamlaAgentUtil::checkBoolField($field, $value, $required, __CLASS__);
                    break;
                case 'paymentMethod':
                case 'currency':
                case 'comment':
                case 'exchangeBank':
                case 'orderNumber':
                case 'correctiveNumber':
                case 'extraLogo':
                case 'prefix':
                case 'invoiceNumber':
                case 'invoiceTemplate':
                case 'prePaymentInvoiceNumber':
                    SzamlaAgentUtil::checkStrField($field, $value, $required, __CLASS__);
                    break;
            }
        }

        return $value;
    }

    protected function checkFields(): static
    {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            $this->checkField($field, $value);
        }

        return $this;
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        if (empty($request)) {
            throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_NOT_AVAILABLE);
        }

        $this->setRequiredFields([
            'invoiceDate', 'fulfillment', 'paymentDue', 'paymentMethod', 'currency', 'language', 'buyer', 'items'
        ]);

        $data = [
            "keltDatum"  => $this->getIssueDate(),
            "teljesitesDatum" => $this->getFulfillment(),
            "fizetesiHataridoDatum" => $this->getPaymentDue(),
            "fizmod" => $this->getPaymentMethod(),
            "penznem" => $this->getCurrency(),
            "szamlaNyelve" => $this->getLanguage()
        ];

        if (SzamlaAgentUtil::isNotBlank($this->getComment())) {
            $data['megjegyzes'] = $this->getComment();
        }

        if (SzamlaAgentUtil::isNotBlank($this->getExchangeBank())) {
            $data['arfolyamBank'] = $this->getExchangeBank();
        }

        if (SzamlaAgentUtil::isNotNull($this->getExchangeRate())) {
            $data['arfolyam'] = SzamlaAgentUtil::doubleFormat($this->getExchangeRate());
        }

        if (SzamlaAgentUtil::isNotBlank($this->getOrderNumber())) {
            $data['rendelesSzam'] = $this->getOrderNumber();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getProformaNumber())) {
            $data['dijbekeroSzamlaszam'] = $this->getProformaNumber();
        }

        if ($this->isPrePayment()) {
            $data['elolegszamla']  = $this->isPrePayment();
        }

        if ($this->isFinal()) {
            $data['vegszamla']  = $this->isFinal();
        }

        if (SzamlaAgentUtil::isNotBlank($this->getPrePaymentInvoiceNumber())) {
            $data['elolegSzamlaszam'] = $this->getPrePaymentInvoiceNumber();
        }

        if ($this->isCorrective()) {
            $data['helyesbitoszamla']  = $this->isCorrective();
        }

        if (SzamlaAgentUtil::isNotBlank($this->getCorrectiveNumber())) {
            $data['helyesbitettSzamlaszam']  = $this->getCorrectiveNumber();
        }

        if ($this->isProforma()) {
            $data['dijbekero']  = $this->isProforma();
        }

        if ($this->isDeliveryNote()) {
            $data['szallitolevel']  = $this->isDeliveryNote();
        }

        if (SzamlaAgentUtil::isNotBlank($this->getExtraLogo())) {
            $data['logoExtra']  = $this->getExtraLogo();
        }

        if (SzamlaAgentUtil::isNotBlank($this->getPrefix())) {
            $data['szamlaszamElotag']  = $this->getPrefix();
        }

        if (SzamlaAgentUtil::isNotNull($this->getCorrectionToPay()) && $this->getCorrectionToPay() !== 0) {
            $data['fizetendoKorrekcio'] = SzamlaAgentUtil::doubleFormat($this->getCorrectionToPay());
        }

        if ($this->isPaid()) {
            $data['fizetve']  = $this->isPaid();
        }

        if ($this->isProfitVat()) {
            $data['arresAfa'] = $this->isProfitVat();
        }

        $data['eusAfa'] = ($this->isEuVat() ? true : false);

        if (SzamlaAgentUtil::isNotBlank($this->getInvoiceTemplate())) {
            $data['szamlaSablon'] = $this->getInvoiceTemplate();
        }

        if ($this->isPreviewPdf()) {
            $data['elonezetpdf']  = $this->isPreviewPdf();
        }

        $this->checkFields();

        return $data;
    }

    public function getIssueDate()
    {
        return $this->issueDate;
    }

    public function setIssueDate($issueDate): static
    {
        $this->issueDate = $issueDate;

        return $this;
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

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getFulfillment()
    {
        return $this->fulfillment;
    }

    public function setFulfillment($fulfillment): static
    {
        $this->fulfillment = $fulfillment;

        return $this;
    }

    public function getPaymentDue()
    {
        return $this->paymentDue;
    }

    public function setPaymentDue($paymentDue): static
    {
        $this->paymentDue = $paymentDue;

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

    public function getExtraLogo()
    {
        return $this->extraLogo;
    }

    public function setExtraLogo($extraLogo): static
    {
        $this->extraLogo = $extraLogo;

        return $this;
    }

    public function getCorrectionToPay()
    {
        return $this->correctionToPay;
    }

    public function setCorrectionToPay($correctionToPay): static
    {
        $this->correctionToPay = (float)$correctionToPay;

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

    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    public function setOrderNumber($orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getPrePaymentInvoiceNumber()
    {
        return $this->prePaymentInvoiceNumber;
    }

    public function setPrePaymentInvoiceNumber($prePaymentInvoiceNumber): static
    {
        $this->prePaymentInvoiceNumber = $prePaymentInvoiceNumber;

        return $this;
    }

    public function getProformaNumber()
    {
        return $this->proformaNumber;
    }

    public function setProformaNumber($proformaNumber): static
    {
        $this->proformaNumber = $proformaNumber;

        return $this;
    }

    public function isPaid()
    {
        return $this->paid;
    }

    public function setPaid($paid): static
    {
        $this->paid = $paid;

        return $this;
    }

    public function isProfitVat()
    {
        return $this->profitVat;
    }

    public function setProfitVat($profitVat): static
    {
        $this->profitVat = $profitVat;

        return $this;
    }

    public function getCorrectiveNumber()
    {
        return $this->correctiveNumber;
    }

    public function setCorrectiveNumber($correctiveNumber)
    {
        $this->correctiveNumber = $correctiveNumber;

        return $this;
    }

    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber($invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getInvoiceTemplate()
    {
        return $this->invoiceTemplate;
    }

    public function setInvoiceTemplate($invoiceTemplate)
    {
        $this->invoiceTemplate = $invoiceTemplate;
    }

    public function getInvoiceType()
    {
        return $this->invoiceType;
    }

    public function setInvoiceType($type): static
    {
        $this->invoiceType = $type;

        return $this;
    }

    public function isEInvoice()
    {
        return $this->getInvoiceType() == Invoice::INVOICE_TYPE_E_INVOICE;
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

    public function isPreviewPdf()
    {
        return $this->previewPdf;
    }

    public function setPreviewPdf($previewPdf): static
    {
        $this->previewPdf = $previewPdf;

        return $this;
    }

    public function isEuVat()
    {
        return $this->euVat;
    }

    public function setEuVat($euVat): static
    {
        $this->euVat = $euVat;

        return $this;
    }
}
