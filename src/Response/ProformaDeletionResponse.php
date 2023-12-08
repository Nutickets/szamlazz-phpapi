<?php

namespace SzamlaAgent\Response;

class ProformaDeletionResponse
{
    protected $proformaNumber;
    protected $errorCode;
    protected $errorMessage;
    protected $success = false;
    protected $headers;

    public static function parseData(array $data)
    {
        $response   = new ProformaDeletionResponse();
        $headers = $data['headers'];

        if (!empty($headers)) {
            $response->setHeaders($headers);

            if (array_key_exists('szlahu_error', $headers)) {
                $error = urldecode($headers['szlahu_error']);
                $response->setErrorMessage($error);
            }

            if (array_key_exists('szlahu_error_code', $headers)) {
                $response->setErrorCode($headers['szlahu_error_code']);
            }

            if ($response->isNotError()) {
                $response->setSuccess(true);
            }
        }

        return $response;
    }

    public function getDocumentNumber()
    {
        return $this->getProformaNumber();
    }

    public function getProformaNumber()
    {
        return $this->proformaNumber;
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

    public function isSuccess()
    {
        return ($this->success && $this->isNotError());
    }

    public function isError()
    {
        return (!empty($this->getErrorMessage()) || !empty($this->getErrorCode()));
    }

    public function isNotError()
    {
        return ! $this->isError();
    }

    protected function setSuccess($success): static
    {
        $this->success = $success;

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
}
