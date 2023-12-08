<?php

namespace SzamlaAgent\Document\Receipt;

use SzamlaAgent\Buyer;
use SzamlaAgent\CreditNote\ReceiptCreditNote;
use SzamlaAgent\Document\Document;
use SzamlaAgent\Header\ReceiptHeader;
use SzamlaAgent\Item\ReceiptItem;
use SzamlaAgent\Seller;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class Receipt extends Document
{
    const CREDIT_NOTES_LIMIT = 5;

    private $header;

    protected $items = [];

    protected $creditNotes = [];

    protected $seller;

    protected $buyer;

    public function __construct($receiptNumber = '')
    {
        if (!empty($receiptNumber)) {
            $this->setHeader(new ReceiptHeader($receiptNumber));
        }
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader(ReceiptHeader $header)
    {
        $this->header = $header;
    }

    public function addItem(ReceiptItem $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setItems($items): static
    {
        $this->items = $items;

        return $this;
    }

    public function addCreditNote(ReceiptCreditNote $creditNote): static
    {
        if (count($this->creditNotes) < self::CREDIT_NOTES_LIMIT) {
            $this->creditNotes[] = $creditNote;
        }

        return $this;
    }

    public function getCreditNotes()
    {
        return $this->creditNotes;
    }

    public function setCreditNotes(array $creditNotes): static
    {
        $this->creditNotes = $creditNotes;

        return $this;
    }

    public function getSeller()
    {
        return $this->seller;
    }

    public function setSeller(Seller $seller): static
    {
        $this->seller = $seller;

        return $this;
    }

    public function getBuyer()
    {
        return $this->buyer;
    }

    public function setBuyer(Buyer $buyer): static
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        $fields = ['beallitasok', 'fejlec'];

        return match ($request->getXmlName()) {
            $request::XML_SCHEMA_CREATE_RECEIPT => $this->buildFieldsData($request, array_merge($fields, ['tetelek', 'kifizetesek'])),
            $request::XML_SCHEMA_CREATE_REVERSE_RECEIPT, $request::XML_SCHEMA_GET_RECEIPT => $this->buildFieldsData($request, $fields),
            $request::XML_SCHEMA_SEND_RECEIPT => $this->buildFieldsData($request, array_merge($fields, ['emailKuldes'])),
            default => throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$request->getXmlName()}"),
        };
    }

    private function buildFieldsData(SzamlaAgentRequest $request, array $fields)
    {
        $data = [];

        if (!empty($fields)) {
            $emailSendingData = $this->buildXmlEmailSendingData();
            foreach ($fields as $key) {
                switch ($key) {
                    case 'beallitasok':
                        $value = $request->getAgent()->getSetting()->buildXmlData($request);
                        break;
                    case 'fejlec':
                        $value = $this->getHeader()->buildXmlData($request);
                        break;
                    case 'tetelek':
                        $value = $this->buildXmlItemsData();
                        break;
                    case 'kifizetesek':
                        $value = (!empty($this->getCreditNotes())) ? $this->buildCreditsXmlData() : null;
                        break;
                    case 'emailKuldes':
                        $value = (!empty($emailSendingData)) ? $emailSendingData : null;
                        break;
                    default:
                        throw new SzamlaAgentException(SzamlaAgentException::XML_KEY_NOT_EXISTS . ": {$key}");
                }

                if (isset($value)) {
                    $data[$key] = $value;
                }
            }
        }
        return $data;
    }

    protected function buildXmlItemsData()
    {
        $data = [];

        if (!empty($this->getItems())) {
            foreach ($this->getItems() as $key => $item) {
                $data["item{$key}"] = $item->buildXmlData();
            }
        }
        return $data;
    }

    protected function buildCreditsXmlData()
    {
        $data = [];
        if (!empty($this->getCreditNotes())) {
            foreach ($this->getCreditNotes() as $key => $note) {
                $data["note{$key}"] = $note->buildXmlData();
            }
        }
        return $data;
    }

    protected function buildXmlEmailSendingData()
    {
        $data = [];

        if (SzamlaAgentUtil::isNotNull($this->getBuyer()) && SzamlaAgentUtil::isNotBlank($this->getBuyer()->getEmail())) {
            $data['email'] = $this->getBuyer()->getEmail();
        }

        if (SzamlaAgentUtil::isNotNull($this->getSeller())) {
            if (SzamlaAgentUtil::isNotBlank($this->getSeller()->getEmailReplyTo())) {
                $data['emailReplyto'] = $this->getSeller()->getEmailReplyTo();
            }
            if (SzamlaAgentUtil::isNotBlank($this->getSeller()->getEmailSubject())) {
                $data['emailTargy']   = $this->getSeller()->getEmailSubject();
            }
            if (SzamlaAgentUtil::isNotBlank($this->getSeller()->getEmailContent())) {
                $data['emailSzoveg']  = $this->getSeller()->getEmailContent();
            }
        }
        return $data;
    }
}
