<?php

namespace SzamlaAgent\Ledger;

use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;

class InvoiceItemLedger extends ItemLedger
{
    protected $economicEventType;
    protected $vatEconomicEventType;
    protected $settlementPeriodStart;
    protected $settlementPeriodEnd;

    public function __construct($economicEventType = '', $vatEconomicEventType = '', $revenueLedgerNumber = '', $vatLedgerNumber = '')
    {
        parent::__construct((string)$revenueLedgerNumber, (string)$vatLedgerNumber);
        $this->setEconomicEventType($economicEventType);
        $this->setVatEconomicEventType($vatEconomicEventType);
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            switch ($field) {
                case 'settlementPeriodStart':
                case 'settlementPeriodEnd':
                    SzamlaAgentUtil::checkDateField($field, $value, false, __CLASS__);
                    break;
                case 'economicEventType':
                case 'vatEconomicEventType':
                case 'revenueLedgerNumber':
                case 'vatLedgerNumber':
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

    public function buildXmlData()
    {
        $data = [];
        $this->checkFields();

        if (SzamlaAgentUtil::isNotBlank($this->getEconomicEventType())) {
            $data['gazdasagiEsem'] = $this->getEconomicEventType();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getVatEconomicEventType())) {
            $data['gazdasagiEsemAfa'] = $this->getVatEconomicEventType();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getRevenueLedgerNumber())) {
            $data['arbevetelFokonyviSzam'] = $this->getRevenueLedgerNumber();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getVatLedgerNumber())) {
            $data['afaFokonyviSzam'] = $this->getVatLedgerNumber();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getSettlementPeriodStart())) {
            $data['elszDatumTol'] = $this->getSettlementPeriodStart();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getSettlementPeriodEnd())) {
            $data['elszDatumIg'] = $this->getSettlementPeriodEnd();
        }

        return $data;
    }

    public function getEconomicEventType()
    {
        return $this->economicEventType;
    }

    public function setEconomicEventType($economicEventType): static
    {
        $this->economicEventType = $economicEventType;

        return $this;
    }

    /**
     * @return string
     */
    public function getVatEconomicEventType()
    {
        return $this->vatEconomicEventType;
    }

    public function setVatEconomicEventType($vatEconomicEventType): static
    {
        $this->vatEconomicEventType = $vatEconomicEventType;

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
