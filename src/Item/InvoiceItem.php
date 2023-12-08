<?php

namespace SzamlaAgent\Item;

use SzamlaAgent\Ledger\InvoiceItemLedger;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class InvoiceItem extends Item
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

        if (SzamlaAgentUtil::isNotNull($this->getPriceGapVatBase())) {
            $data['arresAfaAlap'] = SzamlaAgentUtil::doubleFormat($this->getPriceGapVatBase());
        }

        $data['nettoErtek'] = SzamlaAgentUtil::doubleFormat($this->getNetPrice());
        $data['afaErtek'] = SzamlaAgentUtil::doubleFormat($this->getVatAmount());
        $data['bruttoErtek'] = SzamlaAgentUtil::doubleFormat($this->getGrossAmount());

        if (SzamlaAgentUtil::isNotBlank($this->getComment())) {
            $data['megjegyzes'] = $this->getComment();
        }

        if (SzamlaAgentUtil::isNotNull($this->getLedgerData())) {
            $data['tetelFokonyv'] = $this->getLedgerData()->buildXmlData();
        }

        return $data;
    }

    public function getPriceGapVatBase()
    {
        return $this->priceGapVatBase;
    }

    public function setPriceGapVatBase($priceGapVatBase): static
    {
        $this->priceGapVatBase = (float)$priceGapVatBase;

        return $this;
    }

    public function getLedgerData()
    {
        return $this->ledgerData;
    }

    public function setLedgerData(InvoiceItemLedger $ledgerData): static
    {
        $this->ledgerData = $ledgerData;

        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }
}
