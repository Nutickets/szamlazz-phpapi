<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class ProformaHeader extends InvoiceHeader
{
    protected $requiredFields = [];

    public function __construct()
    {
        parent::__construct();
        $this->setProforma(true);
        $this->setPaid(false);
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        if (empty($request)) {
            throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_NOT_AVAILABLE);
        }

        $data = [];
        switch ($request->getXmlName()) {
            case $request::XML_SCHEMA_DELETE_PROFORMA:
                if (SzamlaAgentUtil::isNotBlank($this->getInvoiceNumber())) {
                    $data["szamlaszam"] = $this->getInvoiceNumber();
                }
                if (SzamlaAgentUtil::isNotBlank($this->getOrderNumber())) {
                    $data["rendelesszam"] = $this->getOrderNumber();
                }
                $this->checkFields();
                break;
            default:
                $data = parent::buildXmlData($request);
        }

        return $data;
    }
}
