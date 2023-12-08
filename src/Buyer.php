<?php

namespace SzamlaAgent;

class Buyer
{
    protected $id;
    protected $name;
    protected $country;
    protected $zipCode;
    protected $city;
    protected $address;
    protected $email;
    protected $sendEmail = true;
    protected $taxPayer;
    protected $taxNumber;
    protected $groupIdentifier;
    protected $taxNumberEU;
    protected $postalName;
    protected $postalCountry;
    protected $postalZip;
    protected $postalCity;
    protected $postalAddress;
    protected $ledgerData;
    protected $signatoryName;
    protected $phone;
    protected $comment;
    protected $requiredFields = [];

    public function __construct($name = '', $zipCode = '', $city = '', $address = '')
    {
        $this->setName($name);
        $this->setZipCode($zipCode);
        $this->setCity($city);
        $this->setAddress($address);
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

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'taxPayer':
                    SzamlaAgentUtil::checkIntField($field, $value, $required, __CLASS__);
                    break;
                case 'sendEmail':
                    SzamlaAgentUtil::checkBoolField($field, $value, $required, __CLASS__);
                    break;
                case 'id':
                case 'email':
                case 'name':
                case 'country':
                case 'zipCode':
                case 'city':
                case 'address':
                case 'taxNumber':
                case 'groupIdentifier':
                case 'taxNumberEU':
                case 'postalName':
                case 'postalCountry':
                case 'postalZip':
                case 'postalCity':
                case 'postalAddress':
                case 'signatoryName':
                case 'phone':
                case 'comment':
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
        $data = [];
        switch ($request->getXmlName()) {
            case $request::XML_SCHEMA_CREATE_INVOICE:
                $this->setRequiredFields(['name', 'zip', 'city', 'address']);

                $data = [
                    "nev"       => $this->getName(),
                    "orszag"    => $this->getCountry(),
                    "irsz"      => $this->getZipCode(),
                    "telepules" => $this->getCity(),
                    "cim"       => $this->getAddress()
                ];

                if (SzamlaAgentUtil::isNotBlank($this->getEmail())) {
                    $data["email"] = $this->getEmail();
                }

                $data["sendEmail"] = $this->isSendEmail() ? true : false;

                if (SzamlaAgentUtil::isNotBlank($this->getTaxPayer())) {
                    $data["adoalany"] = $this->getTaxPayer();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getTaxNumber())) {
                    $data["adoszam"] = $this->getTaxNumber();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getGroupIdentifier())) {
                    $data["csoportazonosito"] = $this->getGroupIdentifier();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getTaxNumberEU())) {
                    $data["adoszamEU"] = $this->getTaxNumberEU();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getPostalName())) {
                    $data["postazasiNev"] = $this->getPostalName();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getPostalCountry())) {
                    $data["postazasiOrszag"] = $this->getPostalCountry();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getPostalZip())) {
                    $data["postazasiIrsz"] = $this->getPostalZip();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getPostalCity())) {
                    $data["postazasiTelepules"] = $this->getPostalCity();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getPostalAddress())) {
                    $data["postazasiCim"] = $this->getPostalAddress();
                }

                if (SzamlaAgentUtil::isNotNull($this->getLedgerData())) {
                    $data["vevoFokonyv"] = $this->getLedgerData()->getXmlData();
                }

                if (SzamlaAgentUtil::isNotBlank($this->getId())) {
                    $data["azonosito"] = $this->getId();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getSignatoryName())) {
                    $data["alairoNeve"] = $this->getSignatoryName();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getPhone())) {
                    $data["telefonszam"] = $this->getPhone();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getComment())) {
                    $data["megjegyzes"] = $this->getComment();
                }
                break;
            case $request::XML_SCHEMA_CREATE_REVERSE_INVOICE:
                if (SzamlaAgentUtil::isNotBlank($this->getEmail())) {
                    $data["email"] = $this->getEmail();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getTaxNumber())) {
                    $data["adoszam"] = $this->getTaxNumber();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getTaxNumberEU())) {
                    $data["adoszamEU"] = $this->getTaxNumberEU();
                }
                break;
            default:
                throw new SzamlaAgentException("Nincs ilyen XML séma definiálva: {$request->getXmlName()}");
        }
        $this->checkFields();

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

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getZipCode()
    {
        return $this->zipCode;
    }

    public function setZipCode($zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isSendEmail()
    {
        return $this->sendEmail;
    }

    public function setSendEmail($sendEmail): static
    {
        $this->sendEmail = $sendEmail;

        return $this;
    }

    public function getTaxPayer()
    {
        return $this->taxPayer;
    }

    public function setTaxPayer($taxPayer): static
    {
        $this->taxPayer = $taxPayer;

        return $this;
    }

    public function getTaxNumber()
    {
        return $this->taxNumber;
    }

    public function setTaxNumber($taxNumber): static
    {
        $this->taxNumber = $taxNumber;

        return $this;
    }

    public function getGroupIdentifier()
    {
        return $this->groupIdentifier;
    }

    public function setGroupIdentifier($groupIdentifier): static
    {
        $this->groupIdentifier = $groupIdentifier;

        return $this;
    }

    public function getTaxNumberEU()
    {
        return $this->taxNumberEU;
    }

    public function setTaxNumberEU($taxNumberEU): static
    {
        $this->taxNumberEU = $taxNumberEU;

        return $this;
    }

    public function getPostalName()
    {
        return $this->postalName;
    }

    public function setPostalName($postalName): static
    {
        $this->postalName = $postalName;

        return $this;
    }

    public function getPostalCountry()
    {
        return $this->postalCountry;
    }

    public function setPostalCountry($postalCountry): static
    {
        $this->postalCountry = $postalCountry;

        return $this;
    }

    public function getPostalZip()
    {
        return $this->postalZip;
    }

    public function setPostalZip($postalZip): static
    {
        $this->postalZip = $postalZip;

        return $this;
    }

    public function getPostalCity()
    {
        return $this->postalCity;
    }

    public function setPostalCity($postalCity): static
    {
        $this->postalCity = $postalCity;

        return $this;
    }

    public function getPostalAddress()
    {
        return $this->postalAddress;
    }

    public function setPostalAddress($postalAddress): static
    {
        $this->postalAddress = $postalAddress;

        return $this;
    }

    public function getLedgerData()
    {
        return $this->ledgerData;
    }

    public function setLedgerData(BuyerLedger $ledgerData): static
    {
        $this->ledgerData = $ledgerData;

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

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($phone): static
    {
        $this->phone = $phone;

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
