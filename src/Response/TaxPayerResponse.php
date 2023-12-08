<?php

namespace SzamlaAgent\Response;

class TaxPayerResponse
{
    protected $requestId;
    protected $timestamp;
    protected $requestVersion;
    protected $funcCode;
    protected $taxpayerValidity;
    private $taxPayerData;
    protected $errorCode;
    protected $errorMessage;

    public static function parseData(array $data)
    {
        $payer = new TaxPayerResponse();

        if (isset($data['result']['funcCode'])) {
            $payer->setFuncCode($data['result']['funcCode']);
        }
        if (isset($data['result']['errorCode'])) {
            $payer->setErrorCode($data['result']['errorCode']);
        }
        if (isset($data['result']['message'])) {
            $payer->setErrorMessage($data['result']['message']);
        }
        if (isset($data['taxpayerValidity'])) {
            $payer->setTaxpayerValidity(($data['taxpayerValidity'] === 'true'));
        }

        if (isset($data['header'])) {
            $header = $data['header'];
            $payer->setRequestId($header['requestId']);
            $payer->setTimestamp($header['timestamp']);
            $payer->setRequestVersion($header['requestVersion']);
        }

        if (isset($data['taxpayerData'])) {
            $payer->setTaxPayerData($data['taxpayerData']);
        }

        return $payer;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    protected function setRequestId($requestId): static
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    protected function setTimestamp($timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getRequestVersion()
    {
        return $this->requestVersion;
    }

    protected function setRequestVersion($requestVersion): static
    {
        $this->requestVersion = $requestVersion;

        return $this;
    }

    public function getFuncCode()
    {
        return $this->funcCode;
    }

    protected function setFuncCode($funcCode): static
    {
        $this->funcCode = $funcCode;

        return $this;
    }

    public function isTaxpayerValidity()
    {
        return $this->taxpayerValidity;
    }

    protected function setTaxpayerValidity($taxpayerValidity): static
    {
        $this->taxpayerValidity = $taxpayerValidity;

        return $this;
    }

    public function hasTaxPayerData()
    {
        return ! empty($this->taxPayerData);
    }

    public function getTaxPayerData()
    {
        return $this->taxPayerData;
    }

    protected function setTaxPayerData(array $data): static
    {
        $this->taxPayerData = $data;

        return $this;
    }

    public function isSuccess()
    {
        return $this->getFuncCode() == 'OK';
    }

    public function isError()
    {
        return ! $this->isSuccess();
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

    public function toString()
    {
        $str = "Tax number validity: " . (($this->isTaxpayerValidity()) ? 'valid' : "invalid");
        if (empty($this->getTaxPayerData()) && $this->getFuncCode()) {
            $str.= ", no data found for the tax number!";
        }

        return $str;
    }
}
