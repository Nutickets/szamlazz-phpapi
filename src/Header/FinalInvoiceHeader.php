<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Invoice\Invoice;

class FinalInvoiceHeader extends InvoiceHeader
{
    public function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE)
    {
        parent::__construct($type);
        $this->setFinal(true);
    }
}
