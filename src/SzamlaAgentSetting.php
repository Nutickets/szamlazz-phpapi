<?php

namespace SzamlaAgent;

use SzamlaAgent\Response\SzamlaAgentResponse;

class SzamlaAgentSetting
{
    const DOWNLOAD_COPIES_COUNT = 1;
    const API_KEY_LENGTH = 42;

    private $username = '';
    private $password = '';
    private $apiKey;
    private $downloadPdf = true;
    private $downloadCopiesCount;
    private $responseType;
    private $aggregator;
    private $guardian;
    private $invoiceItemIdentifier;
    private $invoiceExternalId;
    private $taxNumber;

    public function __construct($username, $password, $apiKey, $downloadPdf = true, $copiesCount = self::DOWNLOAD_COPIES_COUNT, $responseType = SzamlaAgentResponse::RESULT_AS_TEXT, $aggregator = '')
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setApiKey($apiKey);
        $this->setDownloadPdf($downloadPdf);
        $this->setDownloadCopiesCount($copiesCount);
        $this->setResponseType($responseType);
        $this->setAggregator($aggregator);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function isDownloadPdf()
    {
        return $this->downloadPdf;
    }

    public function setDownloadPdf($downloadPdf): static
    {
        $this->downloadPdf = $downloadPdf;

        return $this;
    }

    public function getDownloadCopiesCount()
    {
        return $this->downloadCopiesCount;
    }

    public function setDownloadCopiesCount($downloadCopiesCount): static
    {
        $this->downloadCopiesCount = $downloadCopiesCount;

        return $this;
    }

    public function getResponseType()
    {
        return $this->responseType;
    }

    public function setResponseType($responseType): static
    {
        $this->responseType = $responseType;

        return $this;
    }

    public function getAggregator()
    {
        return $this->aggregator;
    }

    public function setAggregator($aggregator): static
    {
        $this->aggregator = $aggregator;

        return $this;
    }

    public function getGuardian()
    {
        return $this->guardian;
    }

    public function setGuardian($guardian): static
    {
        $this->guardian = $guardian;

        return $this;
    }

    public function isInvoiceItemIdentifier()
    {
        return $this->invoiceItemIdentifier;
    }

    public function setInvoiceItemIdentifier($invoiceItemIdentifier): static
    {
        $this->invoiceItemIdentifier = $invoiceItemIdentifier;

        return $this;
    }

    public function getInvoiceExternalId()
    {
        return $this->invoiceExternalId;
    }

    public function setInvoiceExternalId($invoiceExternalId): static
    {
        $this->invoiceExternalId = $invoiceExternalId;

        return $this;
    }

    public function getTaxNumber()
    {
        return $this->taxNumber;
    }

    public function setTaxNumber($taxNumber): static
    {
        $this->taxNumber = $taxNumber;

        return $this;
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        $settings = ['felhasznalo', 'jelszo', 'szamlaagentkulcs'];

        $data = match ($request->getXmlName()) {
            $request::XML_SCHEMA_CREATE_INVOICE => $this->buildFieldsData($request, array_merge($settings, ['eszamla', 'szamlaLetoltes', 'szamlaLetoltesPld', 'valaszVerzio', 'aggregator', 'guardian', 'cikkazoninvoice', 'szamlaKulsoAzon'])),
            $request::XML_SCHEMA_DELETE_PROFORMA => $this->buildFieldsData($request, $settings),
            $request::XML_SCHEMA_CREATE_REVERSE_INVOICE => $this->buildFieldsData($request, array_merge($settings, ['eszamla', 'szamlaLetoltes', 'szamlaLetoltesPld', 'aggregator', 'guardian', 'valaszVerzio', 'szamlaKulsoAzon'])),
            $request::XML_SCHEMA_PAY_INVOICE => $this->buildFieldsData($request, array_merge($settings, ['szamlaszam', 'adoszam', 'additiv', 'aggregator', 'valaszVerzio'])),
            $request::XML_SCHEMA_REQUEST_INVOICE_XML => $this->buildFieldsData($request, array_merge($settings, ['szamlaszam', 'rendelesSzam', 'pdf'])),
            $request::XML_SCHEMA_REQUEST_INVOICE_PDF => $this->buildFieldsData($request, array_merge($settings, ['szamlaszam', 'rendelesSzam', 'valaszVerzio', 'szamlaKulsoAzon'])),
            $request::XML_SCHEMA_CREATE_RECEIPT, $request::XML_SCHEMA_CREATE_REVERSE_RECEIPT, $request::XML_SCHEMA_GET_RECEIPT => $this->buildFieldsData($request, array_merge($settings, ['pdfLetoltes'])),
            $request::XML_SCHEMA_SEND_RECEIPT, $request::XML_SCHEMA_TAXPAYER => $this->buildFieldsData($request, $settings),
            default => throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$request->getXmlName()}"),
        };

        return $data;
    }

    private function buildFieldsData(SzamlaAgentRequest $request, array $fields)
    {
        $data = [];

        foreach ($fields as $key) {
            $value = match ($key) {
                'felhasznalo' => $this->getUsername(),
                'jelszo' => $this->getPassword(),
                'szamlaagentkulcs' => $this->getApiKey(),
                'szamlaLetoltes', 'pdf', 'pdfLetoltes' => $this->isDownloadPdf(),
                'szamlaLetoltesPld' => $this->getDownloadCopiesCount(),
                'valaszVerzio' => $this->getResponseType(),
                'aggregator' => $this->getAggregator(),
                'guardian' => $this->getGuardian(),
                'cikkazoninvoice' => $this->isInvoiceItemIdentifier(),
                'szamlaKulsoAzon' => $this->getInvoiceExternalId(),
                'eszamla' => $request->getEntity()->getHeader()->isEInvoice(),
                'additiv' => $request->getEntity()->isAdditive(),
                'szamlaszam' => $request->getEntity()->getHeader()->getInvoiceNumber(),
                'rendelesSzam' => $request->getEntity()->getHeader()->getOrderNumber(),
                'adoszam' => $this->getTaxNumber(),
                default => throw new SzamlaAgentException(SzamlaAgentException::XML_KEY_NOT_EXISTS . ": {$key}"),
            };

            if (isset($value)) {
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
