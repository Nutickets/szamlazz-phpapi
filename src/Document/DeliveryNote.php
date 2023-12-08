<?php

namespace SzamlaAgent\Document;

use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Header\DeliveryNoteHeader;

class DeliveryNote extends Invoice
{
    public function __construct()
    {
        parent::__construct(null);
        $this->setHeader(new DeliveryNoteHeader());
    }
}
