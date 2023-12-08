<?php

namespace SzamlaAgent;

class BuyerLedger
{
    protected $buyerId;
    protected $bookingDate;
    protected $buyerLedgerNumber;
    protected $continuedFulfillment;
    protected $settlementPeriodStart;
    protected $settlementPeriodEnd;

    public function __construct($buyerId = '', $bookingDate = '', $buyerLedgerNumber = '', $continuedFulfillment = false)
    {
        $this->setBuyerId($buyerId);
        $this->setBookingDate($bookingDate);
        $this->setBuyerLedgerNumber($buyerLedgerNumber);
        $this->setContinuedFulfillment($continuedFulfillment);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'bookingDate':
                case 'settlementPeriodStart':
                case 'settlementPeriodEnd':
                    SzamlaAgentUtil::checkDateField($field, $value, false, __CLASS__);
                    break;
                case 'continuedFulfillment':
                    SzamlaAgentUtil::checkBoolField($field, $value, false, __CLASS__);
                    break;
                case 'buyerId':
                case 'buyerLedgerNumber':
                    SzamlaAgentUtil::checkStrField($field, $value, false, __CLASS__);
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

    public function getXmlData()
    {
        $data = [];
        $this->checkFields();

        if (SzamlaAgentUtil::isNotBlank($this->getBookingDate())) {
            $data['konyvelesDatum'] = $this->getBookingDate();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getBuyerId())) {
            $data['vevoAzonosito'] = $this->getBuyerId();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getBuyerLedgerNumber())) {
            $data['vevoFokonyviSzam'] = $this->getBuyerLedgerNumber();
        }
        if ($this->isContinuedFulfillment()) {
            $data['folyamatosTelj'] = $this->isContinuedFulfillment();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getSettlementPeriodStart())) {
            $data['elszDatumTol'] = $this->getSettlementPeriodStart();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getSettlementPeriodEnd())) {
            $data['elszDatumIg'] = $this->getSettlementPeriodEnd();
        }

        return $data;
    }

    public function getBuyerId()
    {
        return $this->buyerId;
    }

    public function setBuyerId($buyerId): static
    {
        $this->buyerId = $buyerId;

        return $this;
    }

    public function getBookingDate()
    {
        return $this->bookingDate;
    }

    public function setBookingDate($bookingDate): static
    {
        $this->bookingDate = $bookingDate;

        return $this;
    }

    public function getBuyerLedgerNumber()
    {
        return $this->buyerLedgerNumber;
    }

    public function setBuyerLedgerNumber($buyerLedgerNumber): static
    {
        $this->buyerLedgerNumber = $buyerLedgerNumber;

        return $this;
    }

    public function isContinuedFulfillment()
    {
        return $this->continuedFulfillment;
    }

    public function setContinuedFulfillment($continuedFulfillment): static
    {
        $this->continuedFulfillment = $continuedFulfillment;

        return $this;
    }

    public function getSettlementPeriodStart()
    {
        return $this->settlementPeriodStart;
    }

    public function setSettlementPeriodStart($settlementPeriodStart): static
    {
        $this->settlementPeriodStart = $settlementPeriodStart;

        return $this;
    }

    public function getSettlementPeriodEnd()
    {
        return $this->settlementPeriodEnd;
    }

    public function setSettlementPeriodEnd($settlementPeriodEnd): static
    {
        $this->settlementPeriodEnd = $settlementPeriodEnd;

        return $this;
    }
}
