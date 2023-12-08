<?php

namespace SzamlaAgent\CreditNote;

use SzamlaAgent\Document\Document;

class CreditNote
{
    protected $paymentMode;

    protected $amount;

    protected $description = '';

    protected $requiredFields = ['paymentMode', 'amount'];

    protected function __construct($paymentMode = Document::PAYMENT_METHOD_TRANSFER, $amount = 0.0, $description = '')
    {
        $this->setPaymentMode($paymentMode);
        $this->setAmount($amount);
        $this->setDescription($description);
    }

    protected function getRequiredFields()
    {
        return $this->requiredFields;
    }

    public function getPaymentMode()
    {
        return $this->paymentMode;
    }

    public function setPaymentMode($paymentMode): static
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount): static
    {
        $this->amount = (float) $amount;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description): static
    {
        $this->description = $description;

        return $this;
    }
}
