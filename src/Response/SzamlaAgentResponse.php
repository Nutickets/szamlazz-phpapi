<?php

namespace SzamlaAgent\Response;

use SimpleXMLElement;
use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Header\InvoiceHeader;
use SzamlaAgent\Log;
use SzamlaAgent\SimpleXMLExtended;
use SzamlaAgent\SzamlaAgent;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class SzamlaAgentResponse
{
    const RESULT_AS_TEXT = 1;
    const RESULT_AS_XML = 2;
    const RESULT_AS_TAXPAYER_XML = 3;

    private $agent;
    private $response;
    private $httpCode;
    private $errorMsg = '';
    private $errorCode;
    private $documentNumber;
    private $xmlData;
    private $pdfFile;
    private $content;
    private $responseObj;
    private $xmlSchemaType;

    public function __construct(SzamlaAgent $agent, array $response)
    {
        $this->setAgent($agent);
        $this->setResponse($response);
        $this->setXmlSchemaType($response['headers']['Schema-Type']);
    }

    public function handleResponse()
    {
        $response = $this->getResponse();
        $agent = $this->getAgent();

        if (empty($response)) {
            throw new SzamlaAgentException(SzamlaAgentException::AGENT_RESPONSE_IS_EMPTY);
        }

        if (isset($response['headers']) && !empty($response['headers'])) {
            $headers = $response['headers'];

            if (isset($headers['szlahu_down']) && SzamlaAgentUtil::isNotBlank($headers['szlahu_down'])) {
                throw new SzamlaAgentException(SzamlaAgentException::SYSTEM_DOWN, 500);
            }
        } else {
            throw new SzamlaAgentException(SzamlaAgentException::AGENT_RESPONSE_NO_HEADER);
        }

        if (empty($response['body'])) {
            throw new SzamlaAgentException(SzamlaAgentException::AGENT_RESPONSE_NO_CONTENT);
        }

        if (array_key_exists('http_code', $headers)) {
            $this->setHttpCode($headers['http_code']);
        }

        // XML adatok beállítása és a fájl létrehozása
        if ($this->isXmlResponse()) {
            $this->buildResponseXmlData();
        } else {
            $this->buildResponseTextData();
        }

        $this->buildResponseObjData();
        if ($agent->isXmlFileSave() && $agent->isResponseXmlFileSave()) {
            $this->createXmlFile($this->getXmlData());
        }
        $this->checkFields();

        if ($this->hasInvoiceNotificationSendError()) {
            $agent->writeLog(SzamlaAgentException::INVOICE_NOTIFICATION_SEND_FAILED, Log::LOG_LEVEL_DEBUG);
        }

        if ($this->isFailed()) {
            throw new SzamlaAgentException(SzamlaAgentException::AGENT_ERROR . ": [{$this->getErrorCode()}], {$this->getErrorMsg()}");
        } elseif ($this->isSuccess()) {
            $agent->writeLog("Agent hívás sikeresen befejeződött.", Log::LOG_LEVEL_DEBUG);

            if ($this->isNotTaxPayerXmlResponse()) {
                try {
                    $responseObj = $this->getResponseObj();
                    $this->setDocumentNumber($responseObj->getDocumentNumber());
                    if ($agent->isDownloadPdf()) {
                        $pdfData = $responseObj->getPdfFile();
                        $xmlName = $agent->getRequest()->getXmlName();
                        if (empty($pdfData) && !in_array($xmlName, [SzamlaAgentRequest::XML_SCHEMA_SEND_RECEIPT, SzamlaAgentRequest::XML_SCHEMA_PAY_INVOICE])) {
                            throw new SzamlaAgentException(SzamlaAgentException::DOCUMENT_DATA_IS_MISSING);
                        } elseif (!empty($pdfData)) {
                            $this->setPdfFile($pdfData);

                            if ($agent->isPdfFileSave()) {
                                $file = file_put_contents($this->getPdfFileName(), $pdfData);

                                if ($file !== false) {
                                    $agent->writeLog(SzamlaAgentException::PDF_FILE_SAVE_SUCCESS . ': ' . $this->getPdfFileName(), Log::LOG_LEVEL_DEBUG);
                                } else {
                                    $errorMsg = SzamlaAgentException::PDF_FILE_SAVE_FAILED . ': ' . SzamlaAgentException::FILE_CREATION_FAILED;
                                    $agent->writeLog($errorMsg, Log::LOG_LEVEL_DEBUG);
                                    throw new SzamlaAgentException($errorMsg);
                                }
                            }
                        }
                    } else {
                        $this->setContent($response['body']);
                    }
                } catch (\Exception $e) {
                    $agent->writeLog(SzamlaAgentException::PDF_FILE_SAVE_FAILED . ': ' . $e->getMessage(), Log::LOG_LEVEL_DEBUG);
                    throw $e;
                }
            }
        }
        return $this;
    }

    private function checkFields()
    {
        $response = $this->getResponse();

        if ($this->isAgentInvoiceResponse()) {
            $keys = implode(",", array_keys($response['headers']));
            if (!preg_match('/(szlahu_)/', $keys, $matches)) {
                throw new SzamlaAgentException(SzamlaAgentException::NO_SZLAHU_KEY_IN_HEADER);
            }
        }
    }

    private function createXmlFile(SimpleXMLElement $xml)
    {
        $agent  = $this->getAgent();

        if ($this->isTaxPayerXmlResponse()) {
            $response = $this->getResponse();
            $xml = SzamlaAgentUtil::formatResponseXml($response['body']);
        } else {
            $xml = SzamlaAgentUtil::formatXml($xml);
        }

        $type   = $agent->getResponseType();

        $name = '';
        if ($this->isFailed()) {
            $name = 'error-';
        }
        $name .= strtolower($agent->getRequest()->getXmlName());

        $postfix = match ($type) {
            self::RESULT_AS_XML, self::RESULT_AS_TAXPAYER_XML => "-xml",
            self::RESULT_AS_TEXT => "-text",
            default => throw new SzamlaAgentException(SzamlaAgentException::RESPONSE_TYPE_NOT_EXISTS . " ($type)"),
        };

        $fileName = SzamlaAgentUtil::getXmlFileName('response', $name . $postfix, $agent->getRequest()->getEntity());
        $xml->save($fileName);
        $agent->writeLog("XML fájl mentése sikeres: " . SzamlaAgentUtil::getRealPath($fileName), Log::LOG_LEVEL_DEBUG);
    }

    public function getPdfFileName($withPath = true)
    {
        $header = $this->getAgent()->getRequestEntityHeader();

        if ($header instanceof InvoiceHeader && $header->isPreviewPdf()) {
            $entity = $this->getAgent()->getRequestEntity();

            $name = '';
            if ($entity instanceof Invoice) {
                try {
                    $name .= (new \ReflectionClass($entity))->getShortName() . '-';
                } catch (\ReflectionException $e) {
                    //
                }
            }
            $documentNumber = strtolower($name) . 'preview-' . SzamlaAgentUtil::getDateTimeWithMilliseconds();
        } else {
            $documentNumber = $this->getDocumentNumber();
        }

        if ($withPath) {
            return $this->getPdfFileAbsPath($documentNumber . '.pdf');
        } else {
            return $documentNumber . '.pdf';
        }
    }

    protected function getPdfFileAbsPath($pdfFileName)
    {
        return SzamlaAgentUtil::getAbsPath(SzamlaAgent::PDF_FILE_SAVE_PATH, $pdfFileName);
    }

    public function downloadPdf()
    {
        $pdfFileName = $this->getPdfFileName(false);

        if (SzamlaAgentUtil::isNotBlank($pdfFileName)) {
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename={$pdfFileName}.pdf");
            readfile($this->getPdfFileAbsPath($pdfFileName));
            return true;
        }
        return false;
    }

    public function isSuccess()
    {
        return !$this->isFailed();
    }

    public function isFailed()
    {
        $result = true;
        $obj = $this->getResponseObj();
        if ($obj != null) {
            $result = $obj->isError();
        }
        return $result;
    }

    private function getAgent()
    {
        return $this->agent;
    }

    private function setAgent($agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    private function setResponse(array $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    private function setHttpCode($httpCode): static
    {
        $this->httpCode = $httpCode;

        return $this;
    }

    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    private function setErrorMsg($errorMsg): static
    {
        $this->errorMsg = $errorMsg;

        return $this;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    private function setErrorCode($errorCode): static
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    private function setDocumentNumber($documentNumber): static
    {
        $this->documentNumber = $documentNumber;

        return $this;
    }

    private function setPdfFile($pdfFile): static
    {
        $this->pdfFile = $pdfFile;

        return $this;
    }

    protected function getXmlData()
    {
        return $this->xmlData;
    }

    protected function setXmlData(SimpleXMLElement $xmlData): static
    {
        $this->xmlData = $xmlData;

        return $this;
    }

    protected function getContent()
    {
        return $this->content;
    }

    protected function setContent($content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getTaxPayerStr()
    {
        $result = '';
        if ($this->isTaxPayerXmlResponse()) {
            $result = $this->getResponseObj()->getTaxPayerStr();
        }
        return $result;
    }

    public function getXmlSchemaType()
    {
        return $this->xmlSchemaType;
    }

    protected function setXmlSchemaType($xmlSchemaType): static
    {
        $this->xmlSchemaType = $xmlSchemaType;

        return $this;
    }

    public function getResponseObj()
    {
        return $this->responseObj;
    }

    public function setResponseObj($responseObj): static
    {
        $this->responseObj = $responseObj;

        return $this;
    }

    protected function isAgentInvoiceTextResponse()
    {
        return ($this->isAgentInvoiceResponse() && $this->getAgent()->getResponseType() == self::RESULT_AS_TEXT);
    }

    protected function isAgentInvoiceXmlResponse()
    {
        return ($this->isAgentInvoiceResponse() && $this->getAgent()->getResponseType() == self::RESULT_AS_XML);
    }

    protected function isAgentReceiptTextResponse()
    {
        return ($this->isAgentReceiptResponse() && $this->getAgent()->getResponseType() == self::RESULT_AS_TEXT);
    }

    protected function isAgentReceiptXmlResponse()
    {
        return ($this->isAgentReceiptResponse() && $this->getAgent()->getResponseType() == self::RESULT_AS_XML);
    }

    public function isTaxPayerXmlResponse()
    {
        $result = true;

        if ($this->getXmlSchemaType() != 'taxpayer') {
            return false;
        }

        if ($this->getAgent()->getResponseType() != self::RESULT_AS_TAXPAYER_XML) {
            $result = false;
        }
        return $result;
    }

    public function isNotTaxPayerXmlResponse()
    {
        return !$this->isTaxPayerXmlResponse();
    }

    protected function isXmlResponse()
    {
        return ($this->isAgentInvoiceXmlResponse() || $this->isAgentReceiptXmlResponse() || $this->isTaxPayerXmlResponse());
    }

    public function isAgentInvoiceResponse()
    {
        return ($this->getXmlSchemaType() == Document::DOCUMENT_TYPE_INVOICE);
    }

    public function isAgentProformaResponse()
    {
        return ($this->getXmlSchemaType() == Document::DOCUMENT_TYPE_PROFORMA);
    }

    public function isAgentReceiptResponse()
    {
        return ($this->getXmlSchemaType() == Document::DOCUMENT_TYPE_RECEIPT);
    }

    public function isTaxPayerResponse()
    {
        return ($this->getXmlSchemaType() == 'taxpayer');
    }

    private function buildResponseTextData()
    {
        $response = $this->getResponse();
        $xmlData = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response></response>');
        $headers = $xmlData->addChild('headers');

        foreach ($response['headers'] as $key => $value) {
            $headers->addChild($key, $value);
        }

        if ($this->isAgentReceiptResponse()) {
            $content = base64_encode($response['body']);
        } else {
            $content = ($this->getAgent()->isDownloadPdf()) ? base64_encode($response['body']) : $response['body'];
        }

        $xmlData->addChild('body', $content);

        $this->setXmlData($xmlData);
    }

    private function buildResponseXmlData()
    {
        $response = $this->getResponse();
        if ($this->isTaxPayerXmlResponse()) {
            $xmlData = new SimpleXMLExtended($response['body']);
            $xmlData = SzamlaAgentUtil::removeNamespaces($xmlData);
        } else {
            $xmlData = new SimpleXMLElement($response['body']);
            // Fejléc adatok hozzáadása
            $headers = $xmlData->addChild('headers');
            foreach ($response['headers'] as $key => $header) {
                $headers->addChild($key, $header);
            }
        }
        $this->setXmlData($xmlData);
    }

    public function toPdf()
    {
        return $this->getPdfFile();
    }

    public function getPdfFile()
    {
        return $this->pdfFile;
    }

    public function toXML()
    {
        if (!empty($this->getXmlData())) {
            $data = $this->getXmlData();
            return $data->asXML();
        }
        return null;
    }

    public function toJson()
    {
        $result = json_encode($this->getResponseData());
        if ($result === false || is_null($result) || !SzamlaAgentUtil::isValidJSON($result)) {
            throw new SzamlaAgentException(SzamlaAgentException::INVALID_JSON);
        }
        return $result;
    }

    protected function toArray()
    {
        return json_decode($this->toJson(), true);
    }

    public function getData()
    {
        return $this->toArray();
    }

    public function getDataObj()
    {
        return $this->getResponseObj();
    }

    public function getResponseData()
    {
        if ($this->isNotTaxPayerXmlResponse()) {
            $result['documentNumber'] = $this->getDocumentNumber();
        }

        if (!empty($this->getXmlData())) {
            $result['result'] = $this->getXmlData();
        } else {
            $result['result'] = $this->getContent();
        }
        return $result;
    }

    private function buildResponseObjData()
    {
        $obj    = null;
        $type   = $this->getAgent()->getResponseType();
        $result = $this->getData()['result'];

        if ($this->isAgentInvoiceResponse()) {
            $obj = InvoiceResponse::parseData($result, $type);
        } elseif ($this->isAgentProformaResponse()) {
            $obj = ProformaDeletionResponse::parseData($result);
        } elseif ($this->isAgentReceiptResponse()) {
            $obj = ReceiptResponse::parseData($result, $type);
        } elseif ($this->isTaxPayerXmlResponse()) {
            $obj = TaxPayerResponse::parseData($result);
        }

        $this->setResponseObj($obj);

        if ($obj->isError() || $this->hasInvoiceNotificationSendError()) {
            $this->setErrorCode($obj->getErrorCode());
            $this->setErrorMsg($obj->getErrorMessage());
        }
    }

    public function hasInvoiceNotificationSendError()
    {
        if ($this->isAgentInvoiceResponse() && $this->getResponseObj()->hasInvoiceNotificationSendError()) {
            return true;
        }

        return false;
    }

    public function getTaxPayerData()
    {
        $data = null;
        if ($this->isTaxPayerResponse()) {
            $response = $this->getResponse();
            $data = $response['body'];
        }
        return $data;
    }

    public function getCookieSessionId()
    {
        return $this->agent->getCookieSessionId();
    }

}
