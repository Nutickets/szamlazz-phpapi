<?php

namespace SzamlaAgent\Header;

class DeliveryNoteHeader extends InvoiceHeader
{
    public function __construct()
    {
        parent::__construct();
        $this->setDeliveryNote(true);
        $this->setPaid(false);
    }
}
