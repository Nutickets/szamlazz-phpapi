<?php

namespace SzamlaAgent\Item;

use SzamlaAgent\Ledger\ReceiptItemLedger;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class ReceiptItem extends Item
{
    protected $ledgerData;

    public function __construct($name, $netUnitPrice, $quantity = self::DEFAULT_QUANTITY, $quantityUnit = self::DEFAULT_QUANTITY_UNIT, $vat = self::DEFAULT_VAT)
    {
        parent::__construct($name, $netUnitPrice, $quantity, $quantityUnit, $vat);
    }

    public function buildXmlData()
    {
        $data = [];
        $this->checkFields();

        $data['megnevezes'] = $this->getName();

        if (SzamlaAgentUtil::isNotBlank($this->getId())) {
            $data['azonosito'] = $this->getId();
        }

        $data['mennyiseg'] = SzamlaAgentUtil::doubleFormat($this->getQuantity());
        $data['mennyisegiEgyseg'] = $this->getQuantityUnit();
        $data['nettoEgysegar'] = SzamlaAgentUtil::doubleFormat($this->getNetUnitPrice());
        $data['afakulcs'] = $this->getVat();
        $data['netto'] = SzamlaAgentUtil::doubleFormat($this->getNetPrice());
        $data['afa'] = SzamlaAgentUtil::doubleFormat($this->getVatAmount());
        $data['brutto'] = SzamlaAgentUtil::doubleFormat($this->getGrossAmount());

        if (SzamlaAgentUtil::isNotNull($this->getLedgerData())) {
            $data['fokonyv'] = $this->getLedgerData()->buildXmlData();
        }

        return $data;
    }

    public function getLedgerData()
    {
        return $this->ledgerData;
    }

    public function setLedgerData(ReceiptItemLedger $ledgerData): static
    {
        $this->ledgerData = $ledgerData;

        return $this;
    }
}
