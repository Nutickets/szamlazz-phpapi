<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Invoice\Invoice;

class CorrectiveInvoiceHeader extends InvoiceHeader
{
    public function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE)
    {
        parent::__construct($type);
        $this->setCorrective(true);
    }
}
