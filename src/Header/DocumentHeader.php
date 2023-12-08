<?php

namespace SzamlaAgent\Header;

class DocumentHeader
{
    protected $invoice = false;
    protected $reserveInvoice = false;
    protected $prePayment = false;
    protected $finalValue = false;
    protected $corrective = false;
    protected $proforma = false;
    protected $deliveryNote = false;
    protected $receipt = false;
    protected $reverseReceipt = false;

    public function isInvoice()
    {
        return $this->invoice;
    }

    public function setInvoice($invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function isReserveInvoice()
    {
        return $this->reserveInvoice;
    }

    public function isNotReserveInvoice()
    {
        return ! $this->reserveInvoice;
    }

    public function setReserveInvoice($reserveInvoice)
    {
        $this->reserveInvoice = $reserveInvoice;
    }

    public function isPrePayment()
    {
        return $this->prePayment;
    }

    public function setPrePayment($prePayment): static
    {
        $this->prePayment = $prePayment;

        return $this;
    }

    public function isFinal()
    {
        return $this->finalValue;
    }

    public function setFinal($finalValue): static
    {
        $this->finalValue = $finalValue;

        return $this;
    }

    public function isCorrective()
    {
        return $this->corrective;
    }

    public function setCorrective($corrective): static
    {
        $this->corrective = $corrective;

        return $this;
    }

    public function isProforma()
    {
        return $this->proforma;
    }

    public function setProforma($proforma): static
    {
        $this->proforma = $proforma;

        return $this;
    }

    public function isDeliveryNote()
    {
        return $this->deliveryNote;
    }

    public function setDeliveryNote($deliveryNote): static
    {
        $this->deliveryNote = $deliveryNote;

        return $this;
    }

    public function isReceipt()
    {
        return $this->receipt;
    }

    public function setReceipt($receipt): static
    {
        $this->receipt = $receipt;

        return $this;
    }

    public function isReverseReceipt()
    {
        return $this->reverseReceipt;
    }

    public function setReverseReceipt($reverseReceipt): static
    {
        $this->reverseReceipt = $reverseReceipt;

        return $this;
    }
}
