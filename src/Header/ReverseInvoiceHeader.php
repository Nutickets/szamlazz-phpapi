<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class ReverseInvoiceHeader extends InvoiceHeader
{
    protected $requiredFields = ['invoiceNumber'];

    public function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE)
    {
        parent::__construct($type);
        $this->setReserveInvoice(true);
    }

    public function checkField($field, $value)
    {
        if (property_exists(get_parent_class($this), $field) || property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'issueDate':
                case 'fulfillment':
                    SzamlaAgentUtil::checkDateField($field, $value, $required, __CLASS__);
                    break;
                case 'invoiceNumber':
                case 'comment':
                    SzamlaAgentUtil::checkStrField($field, $value, $required, __CLASS__);
                    break;
            }
        }

        return $value;
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        if (empty($request)) {
            throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_NOT_AVAILABLE);
        }

        $data["szamlaszam"] = $this->getInvoiceNumber();

        if (!empty($this->getIssueDate())) {
            $data['keltDatum'] = $this->getIssueDate();
        }
        if (!empty($this->getFulfillment())) {
            $data['teljesitesDatum'] = $this->getFulfillment();
        }
        if (SzamlaAgentUtil::isNotBlank($this->getComment())) {
            $data['megjegyzes'] = $this->getComment();
        }

        $data['tipus'] = Document::DOCUMENT_TYPE_REVERSE_INVOICE_CODE;

        if (!empty($this->getInvoiceTemplate())) {
            $data['szamlaSablon'] = $this->getInvoiceTemplate();
        }

        $this->checkFields();

        return $data;
    }
}
