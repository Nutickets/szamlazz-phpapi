<?php

namespace SzamlaAgent\Document;

use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Header\ProformaHeader;

class Proforma extends Invoice
{
    const FROM_INVOICE_NUMBER = 1;
    const FROM_ORDER_NUMBER = 2;

    public function __construct()
    {
        parent::__construct(null);
        $this->setHeader(new ProformaHeader());
    }
}
