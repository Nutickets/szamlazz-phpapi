<?php

namespace SzamlaAgent\Document\Invoice;

use SzamlaAgent\Header\CorrectiveInvoiceHeader;

class CorrectiveInvoice extends Invoice
{
    public function __construct($type = self::INVOICE_TYPE_P_INVOICE)
    {
        parent::__construct(null);
        $this->setHeader(new CorrectiveInvoiceHeader($type));
    }
}
