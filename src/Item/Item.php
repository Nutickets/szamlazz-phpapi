<?php

namespace SzamlaAgent\Item;

use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class Item
{
    const VAT_TAM = 'TAM';
    const VAT_AAM = 'AAM';
    const VAT_EU = 'EU';
    const VAT_EUK = 'EUK';
    const VAT_MAA = 'MAA';
    const VAT_F_AFA = 'F.AFA';
    const VAT_K_AFA = 'K.AFA';
    const VAT_AKK = 'ÁKK';
    const VAT_TAHK = 'TAHK';
    const VAT_TEHK = 'TEHK';
    const VAT_EUT = 'EUT';
    const VAT_EUKT = 'EUKT';
    const VAT_KBAET = 'KBAET';
    const VAT_KBAUK = 'KBAUK';
    const VAT_EAM = 'EAM';
    const VAT_NAM = 'KBAUK';
    const VAT_ATK = 'ATK';
    const VAT_EUFAD37 = 'EUFAD37';
    const VAT_EUFADE = 'EUFADE';
    const VAT_EUE = 'EUE';
    const VAT_HO = 'HO';
    const DEFAULT_VAT = '27';
    const DEFAULT_QUANTITY = 1.0;
    const DEFAULT_QUANTITY_UNIT = 'db';

    protected $id;
    protected $name;
    protected $quantity;
    protected $quantityUnit;
    protected $netUnitPrice;
    protected $vat;
    protected $priceGapVatBase;
    protected $netPrice;
    protected $vatAmount;
    protected $grossAmount;
    protected $comment;
    protected $requiredFields = ['name', 'quantity', 'quantityUnit', 'netUnitPrice', 'vat', 'netPrice', 'vatAmount', 'grossAmount'];

    protected function __construct($name, $netUnitPrice, $quantity = self::DEFAULT_QUANTITY, $quantityUnit = self::DEFAULT_QUANTITY_UNIT, $vat = self::DEFAULT_VAT)
    {
        $this->setName($name);
        $this->setNetUnitPrice($netUnitPrice);
        $this->setQuantity($quantity);
        $this->setQuantityUnit($quantityUnit);
        $this->setVat($vat);
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
                case 'quantity':
                case 'netUnitPrice':
                case 'priceGapVatBase':
                case 'netPrice':
                case 'vatAmount':
                case 'grossAmount':
                    SzamlaAgentUtil::checkDoubleField($field, $value, $required, __CLASS__);
                    break;
                case 'name':
                case 'id':
                case 'quantityUnit':
                case 'vat':
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
            $this::checkField($field, $value);
        }
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

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($quantity): static
    {
        $this->quantity = (float)$quantity;

        return $this;
    }

    public function getQuantityUnit()
    {
        return $this->quantityUnit;
    }

    public function setQuantityUnit($quantityUnit): static
    {
        $this->quantityUnit = $quantityUnit;

        return $this;
    }

    public function getNetUnitPrice()
    {
        return $this->netUnitPrice;
    }

    public function setNetUnitPrice($netUnitPrice): static
    {
        $this->netUnitPrice = (float)$netUnitPrice;

        return $this;
    }

    public function getVat()
    {
        return $this->vat;
    }

    public function setVat($vat): static
    {
        $this->vat = $vat;

        return $this;
    }

    public function getNetPrice()
    {
        return $this->netPrice;
    }

    public function setNetPrice($netPrice): static
    {
        $this->netPrice = (float)$netPrice;

        return $this;
    }

    public function getVatAmount()
    {
        return $this->vatAmount;
    }

    public function setVatAmount($vatAmount): static
    {
        $this->vatAmount = (float)$vatAmount;

        return $this;
    }

    public function getGrossAmount()
    {
        return $this->grossAmount;
    }

    public function setGrossAmount($grossAmount): static
    {
        $this->grossAmount = (float)$grossAmount;

        return $this;
    }

    public static function vatCodes(): array
    {
        return array_values(array_unique([
            static::VAT_TAM,
            static::VAT_AAM,
            static::VAT_EU,
            static::VAT_EUK,
            static::VAT_MAA,
            static::VAT_F_AFA,
            static::VAT_K_AFA,
            static::VAT_AKK,
            static::VAT_TAHK,
            static::VAT_TEHK,
            static::VAT_EUT,
            static::VAT_EUKT,
            static::VAT_KBAET,
            static::VAT_KBAUK,
            static::VAT_EAM,
            static::VAT_NAM,
            static::VAT_ATK,
            static::VAT_EUFAD37,
            static::VAT_EUFADE,
            static::VAT_EUE,
            static::VAT_HO,
        ]));
    }
}
