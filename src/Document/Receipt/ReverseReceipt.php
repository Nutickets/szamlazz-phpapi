<?php

namespace SzamlaAgent\Document\Receipt;

use SzamlaAgent\Header\ReverseReceiptHeader;

class ReverseReceipt extends Receipt
{
    public function __construct($receiptNumber = '')
    {
        parent::__construct(null);
        $this->setHeader(new ReverseReceiptHeader($receiptNumber));
    }
}
