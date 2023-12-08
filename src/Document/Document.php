<?php

namespace SzamlaAgent\Document;

use SzamlaAgent\Currency;
use SzamlaAgent\Language;

class Document
{
    const PAYMENT_METHOD_TRANSFER               = 'átutalás';
    const PAYMENT_METHOD_CASH                   = 'készpénz';
    const PAYMENT_METHOD_BANKCARD               = 'bankkártya';
    const PAYMENT_METHOD_CHEQUE                 = 'csekk';
    const PAYMENT_METHOD_CASH_ON_DELIVERY       = 'utánvét';
    const PAYMENT_METHOD_PAYPAL                 = 'PayPal';
    const PAYMENT_METHOD_SZEP_CARD              = 'SZÉP kártya';
    const PAYMENT_METHOD_OTP_SIMPLE             = 'OTP Simple';
    const DOCUMENT_TYPE_INVOICE                 = 'invoice';
    const DOCUMENT_TYPE_INVOICE_CODE            = 'SZ';
    const DOCUMENT_TYPE_REVERSE_INVOICE         = 'reverseInvoice';
    const DOCUMENT_TYPE_REVERSE_INVOICE_CODE    = 'SS';
    const DOCUMENT_TYPE_PAY_INVOICE             = 'payInvoice';
    const DOCUMENT_TYPE_PAY_INVOICE_CODE        = 'JS';
    const DOCUMENT_TYPE_CORRECTIVE_INVOICE      = 'correctiveInvoice';
    const DOCUMENT_TYPE_CORRECTIVE_INVOICE_CODE = 'HS';
    const DOCUMENT_TYPE_PREPAYMENT_INVOICE      = 'prePaymentInvoice';
    const DOCUMENT_TYPE_PREPAYMENT_INVOICE_CODE = 'ES';
    const DOCUMENT_TYPE_FINAL_INVOICE           = 'finalInvoice';
    const DOCUMENT_TYPE_FINAL_INVOICE_CODE      = 'VS';
    const DOCUMENT_TYPE_PROFORMA                = 'proforma';
    const DOCUMENT_TYPE_PROFORMA_CODE           = 'D';
    const DOCUMENT_TYPE_DELIVERY_NOTE           = 'deliveryNote';
    const DOCUMENT_TYPE_DELIVERY_NOTE_CODE      = 'SL';
    const DOCUMENT_TYPE_RECEIPT                 = 'receipt';
    const DOCUMENT_TYPE_RECEIPT_CODE            = 'NY';
    const DOCUMENT_TYPE_RESERVE_RECEIPT         = 'reserveReceipt';
    const DOCUMENT_TYPE_RESERVE_RECEIPT_CODE    = 'SN';

    public static function getDefaultCurrency()
    {
        return Currency::getDefault();
    }

    public static function getDefaultLanguage()
    {
        return Language::getDefault();
    }
}
