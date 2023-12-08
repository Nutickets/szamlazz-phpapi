<?php

namespace SzamlaAgent;

class Seller{

    protected $bank;
    protected $bankAccount;
    protected $emailReplyTo;
    protected $emailSubject;
    protected $emailContent;
    protected $signatoryName;

    function __construct($bank = '', $bankAccount = '') {
        $this->setBank($bank);
        $this->setBankAccount($bankAccount);
    }

    protected function checkField($field, $value) {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'bank':
                case 'bankAccount':
                case 'emailReplyTo':
                case 'emailSubject':
                case 'emailContent':
                case 'signatoryName':
                    SzamlaAgentUtil::checkStrField($field, $value, false, __CLASS__);
                    break;
            }
        }

        return $value;
    }

    protected function checkFields() {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            $this->checkField($field, $value);
        }
    }

    public function buildXmlData(SzamlaAgentRequest $request) {
        $data = [];

        $this->checkFields();

        switch ($request->getXmlName()) {
            case $request::XML_SCHEMA_CREATE_INVOICE:
                if (SzamlaAgentUtil::isNotBlank($this->getBank())) {
                    $data["bank"] = $this->getBank();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getBankAccount())) {
                    $data["bankszamlaszam"] = $this->getBankAccount();
                }

                $emailData = $this->getXmlEmailData();
                if (!empty($emailData)) {
                    $data = array_merge($data, $emailData);
                }
                if (SzamlaAgentUtil::isNotBlank($this->getSignatoryName())) {
                    $data["alairoNeve"] = $this->getSignatoryName();
                }
                break;
            case $request::XML_SCHEMA_CREATE_REVERSE_INVOICE:
                $data = $this->getXmlEmailData();
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$request->getXmlName()}");
        }

        return $data;
    }

    protected function getXmlEmailData() {
        $data = [];
        if (SzamlaAgentUtil::isNotBlank($this->getEmailReplyTo()))  $data["emailReplyto"] = $this->getEmailReplyTo();
        if (SzamlaAgentUtil::isNotBlank($this->getEmailSubject()))  $data["emailTargy"] = $this->getEmailSubject();
        if (SzamlaAgentUtil::isNotBlank($this->getEmailContent()))  $data["emailSzoveg"] = $this->getEmailContent();
        return $data;
    }

    public function getBank()
    {
        return $this->bank;
    }

    public function setBank($bank): static
    {
        $this->bank = $bank;

        return $this;
    }

    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    public function setBankAccount($bankAccount): static
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    public function getEmailReplyTo()
    {
        return $this->emailReplyTo;
    }

    public function setEmailReplyTo($emailReplyTo): static
    {
        $this->emailReplyTo = $emailReplyTo;

        return $this;
    }

    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    public function setEmailSubject($emailSubject): static
    {
        $this->emailSubject = $emailSubject;

        return $this;
    }

    public function getEmailContent()
    {
        return $this->emailContent;
    }

    public function setEmailContent($emailContent): static
    {
        $this->emailContent = $emailContent;

        return $this;
    }

    public function getSignatoryName()
    {
        return $this->signatoryName;
    }

    public function setSignatoryName($signatoryName): static
    {
        $this->signatoryName = $signatoryName;

        return $this;
    }
}
