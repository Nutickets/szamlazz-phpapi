<?php

namespace SzamlaAgent\Response;

use SzamlaAgent\SzamlaAgentUtil;

class InvoiceResponse
{
    const INVOICE_NOTIFICATION_SEND_FAILED = 56;

    protected $userAccountUrl;
    protected $assetAmount;
    protected $netPrice;
    protected $grossAmount;
    protected $invoiceNumber;
    protected $errorCode;
    protected $errorMessage;
    protected $pdfData;
    protected $success;
    protected $headers;

    public function __construct($invoiceNumber = '')
    {
        $this->setInvoiceNumber($invoiceNumber);
    }

    public static function parseData(array $data, $type = SzamlaAgentResponse::RESULT_AS_TEXT)
    {
        $response = new InvoiceResponse();
        $headers = $data['headers'];
        $isPdf = self::isPdfResponse($data);
        $pdfFile = '';

        if (isset($data['body'])) {
            $pdfFile = $data['body'];
        } elseif ($type == SzamlaAgentResponse::RESULT_AS_XML && isset($data['pdf'])) {
            $pdfFile = $data['pdf'];
        }

        if (!empty($headers)) {
            $response->setHeaders($headers);

            if (array_key_exists('szlahu_szamlaszam', $headers)) {
                $response->setInvoiceNumber($headers['szlahu_szamlaszam']);
            }

            if (array_key_exists('szlahu_vevoifiokurl', $headers)) {
                $response->setUserAccountUrl(rawurldecode($headers['szlahu_vevoifiokurl']));
            }

            if (array_key_exists('szlahu_kintlevoseg', $headers)) {
                $response->setAssetAmount($headers['szlahu_kintlevoseg']);
            }

            if (array_key_exists('szlahu_nettovegosszeg', $headers)) {
                $response->setNetPrice($headers['szlahu_nettovegosszeg']);
            }

            if (array_key_exists('szlahu_bruttovegosszeg', $headers)) {
                $response->setGrossAmount($headers['szlahu_bruttovegosszeg']);
            }

            if (array_key_exists('szlahu_error', $headers)) {
                $error = urldecode($headers['szlahu_error']);
                $response->setErrorMessage($error);
            }

            if (array_key_exists('szlahu_error_code', $headers)) {
                $response->setErrorCode($headers['szlahu_error_code']);
            }

            if ($isPdf && !empty($pdfFile)) {
                $response->setPdfData($pdfFile);
            }

            if ($response->isNotError()) {
                $response->setSuccess(true);
            }
        }

        return $response;
    }

    protected static function isPdfResponse($result)
    {
        if (isset($result['pdf'])) {
            return true;
        }

        if (isset($result['headers']['Content-Type']) && $result['headers']['Content-Type'] == 'application/pdf') {
            return true;
        }

        if (isset($result['headers']['Content-Disposition']) && stripos($result['headers']['Content-Disposition'], 'pdf') !== false) {
            return true;
        }

        return false;
    }

    public function hasInvoiceNumber()
    {
        return (SzamlaAgentUtil::isNotBlank($this->invoiceNumber));
    }

    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    public function getDocumentNumber()
    {
        return $this->getInvoiceNumber();
    }

    protected function setInvoiceNumber($invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    protected function setErrorCode($errorCode): static
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    protected function setErrorMessage($errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getPdfFile()
    {
        $pdfData = SzamlaAgentUtil::isNotNull($this->getPdfData()) ? $this->getPdfData() : '';
        return base64_decode($pdfData);
    }

    public function getPdfData()
    {
        return $this->pdfData;
    }

    protected function setPdfData($pdfData): static
    {
        $this->pdfData = $pdfData;

        return $this;
    }

    public function isSuccess()
    {
        return ($this->success && $this->isNotError());
    }

    public function isError()
    {
        $result = false;
        if (!empty($this->getErrorMessage()) || !empty($this->getErrorCode())) {
            $result = true;
        }

        if ($this->hasInvoiceNumber() && $this->hasInvoiceNotificationSendError()) {
            $result = false;
        }

        return $result;
    }

    public function isNotError()
    {
        return !$this->isError();
    }

    protected function setSuccess($success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getUserAccountUrl()
    {
        return urldecode($this->userAccountUrl);
    }

    protected function setUserAccountUrl($userAccountUrl): static
    {
        $this->userAccountUrl = $userAccountUrl;

        return $this;
    }

    public function getAssetAmount()
    {
        return $this->assetAmount;
    }

    protected function setAssetAmount($assetAmount): static
    {
        $this->assetAmount = $assetAmount;

        return $this;
    }

    public function getNetPrice()
    {
        return $this->netPrice;
    }

    protected function setNetPrice($netPrice): static
    {
        $this->netPrice = $netPrice;

        return $this;
    }

    public function getGrossAmount()
    {
        return $this->grossAmount;
    }

    protected function setGrossAmount($grossAmount): static
    {
        $this->grossAmount = $grossAmount;

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    protected function setHeaders($headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    public function hasInvoiceNotificationSendError()
    {
        if ($this->getErrorCode() == self::INVOICE_NOTIFICATION_SEND_FAILED) {
            return true;
        }

        return false;
    }
}
