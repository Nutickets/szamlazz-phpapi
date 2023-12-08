<?php

namespace SzamlaAgent\Ledger;

class ItemLedger
{
    protected $revenueLedgerNumber;
    protected $vatLedgerNumber;

    protected function __construct($revenueLedgerNumber = '', $vatLedgerNumber = '')
    {
        $this->setRevenueLedgerNumber($revenueLedgerNumber);
        $this->setVatLedgerNumber($vatLedgerNumber);
    }

    public function getRevenueLedgerNumber()
    {
        return $this->revenueLedgerNumber;
    }

    public function setRevenueLedgerNumber($revenueLedgerNumber): static
    {
        $this->revenueLedgerNumber = $revenueLedgerNumber;

        return $this;
    }

    public function getVatLedgerNumber()
    {
        return $this->vatLedgerNumber;
    }

    public function setVatLedgerNumber($vatLedgerNumber): static
    {
        $this->vatLedgerNumber = $vatLedgerNumber;

        return $this;
    }
}
