<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Invoice\Invoice;

class PrePaymentInvoiceHeader extends InvoiceHeader
{
    public function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE)
    {
        parent::__construct($type);
        $this->setPrePayment(true);
        $this->setPaid(false);
    }
}
