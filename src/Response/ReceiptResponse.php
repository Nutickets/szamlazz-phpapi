<?php

namespace SzamlaAgent\Response;

use SzamlaAgent\SzamlaAgentUtil;

class ReceiptResponse {
    protected $id;
    protected $receiptNumber;
    protected $type;
    protected $reserved;
    protected $reservedReceiptNumber;
    protected $created;
    protected $paymentMethod;
    protected $currency;
    protected $test;
    protected $items;
    protected $amounts;
    protected $errorCode;
    protected $errorMessage;
    protected $pdfData;
    protected $success;
    protected $creditNotes;

    public function __construct($receiptNumber = '')
    {
        $this->setReceiptNumber($receiptNumber);
    }

    public static function parseData(array $data, $type = SzamlaAgentResponse::RESULT_AS_TEXT)
    {
        $response = new ReceiptResponse();

        if ($type == SzamlaAgentResponse::RESULT_AS_TEXT) {
            $params = $xmlData = new \SimpleXMLElement(base64_decode($data['body']));
            $data = SzamlaAgentUtil::toArray($params);
        }

        $base = [];
        if (isset($data['nyugta']['alap']))        $base = $data['nyugta']['alap'];

        if (isset($base['id'])) {
            $response->setId($base['id']);
        }
        if (isset($base['nyugtaszam'])) {
            $response->setReceiptNumber($base['nyugtaszam']);
        }
        if (isset($base['tipus'])) {
            $response->setType($base['tipus']);
        }
        if (isset($base['stornozott'])) {
            $response->setReserved(($base['stornozott'] === 'true'));
        }
        if (isset($base['stornozottNyugtaszam'])) {
            $response->setReservedReceiptNumber($base['stornozottNyugtaszam']);
        }
        if (isset($base['kelt'])) {
            $response->setCreated($base['kelt']);
        }
        if (isset($base['fizmod'])) {
            $response->setPaymentMethod($base['fizmod']);
        }
        if (isset($base['penznem'])) {
            $response->setCurrency($base['penznem']);
        }
        if (isset($base['teszt'])) {
            $response->setTest(($base['teszt'] === 'true'));
        }
        if (isset($data['nyugta']['tetelek'])) {
            $response->setItems($data['nyugta']['tetelek']);
        }
        if (isset($data['nyugta']['osszegek'])) {
            $response->setAmounts($data['nyugta']['osszegek']);
        }
        if (isset($data['nyugta']['kifizetesek'])) {
            $response->setCreditNotes($data['nyugta']['kifizetesek']);
        }
        if (isset($data['sikeres'])) {
            $response->setSuccess(($data['sikeres'] === 'true'));
        }

        if (isset($data['nyugtaPdf'])) {
            $response->setPdfData($data['nyugtaPdf']);
        }
        if (isset($data['hibakod'])) {
            $response->setErrorCode($data['hibakod']);
        }
        if (isset($data['hibauzenet'])) {
            $response->setErrorMessage($data['hibauzenet']);
        }

        return $response;
    }

    public function getId()
    {
        return $this->id;
    }

    protected function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getReceiptNumber()
    {
        return $this->receiptNumber;
    }

    public function getDocumentNumber()
    {
        return $this->getReceiptNumber();
    }

    protected function setReceiptNumber($receiptNumber): static
    {
        $this->receiptNumber = $receiptNumber;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    protected function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getReserved()
    {
        return $this->reserved;
    }

    protected function setReserved($reserved): static
    {
        $this->reserved = $reserved;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    protected function setCreated($created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    protected function setPaymentMethod($paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    protected function setCurrency($currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function isTest()
    {
        return $this->test;
    }

    protected function setTest($test): static
    {
        $this->test = $test;

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    protected function setItems($items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getAmounts()
    {
        return $this->amounts;
    }

    protected function setAmounts($amounts): static
    {
        $this->amounts = $amounts;

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
        return $this->success;
    }

    public function isError()
    {
        return !$this->isSuccess();
    }

    protected function setSuccess($success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getCreditNotes()
    {
        return $this->creditNotes;
    }

    protected function setCreditNotes($creditNotes): static
    {
        $this->creditNotes = $creditNotes;

        return $this;
    }

    public function getReservedReceiptNumber()
    {
        return $this->reservedReceiptNumber;
    }

    protected function setReservedReceiptNumber($reservedReceiptNumber): static
    {
        $this->reservedReceiptNumber = $reservedReceiptNumber;

        return $this;
    }
}
