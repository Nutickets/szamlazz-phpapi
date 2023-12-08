<?php

namespace SzamlaAgent\Document\Invoice;

use SzamlaAgent\Header\ReverseInvoiceHeader;

class ReverseInvoice extends Invoice
{
    public function __construct($type = self::INVOICE_TYPE_P_INVOICE)
    {
        parent::__construct(null);
        if (!empty($type)) {
            $this->setHeader(new ReverseInvoiceHeader($type));
        }
    }
}
