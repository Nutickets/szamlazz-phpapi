<?php

namespace SzamlaAgent\Document\Invoice;

use SzamlaAgent\Document\Document;
use SzamlaAgent\Header\InvoiceHeader;
use SzamlaAgent\Item\InvoiceItem;
use SzamlaAgent\CreditNote\InvoiceCreditNote;
use SzamlaAgent\Log;
use SzamlaAgent\Waybill\Waybill;
use SzamlaAgent\Buyer;
use SzamlaAgent\Seller;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentRequest;
use SzamlaAgent\SzamlaAgentUtil;

class Invoice extends Document
{
    const INVOICE_TYPE_P_INVOICE = 1;
    const INVOICE_TYPE_E_INVOICE = 2;
    const FROM_INVOICE_NUMBER = 1;
    const FROM_ORDER_NUMBER = 2;
    const FROM_INVOICE_EXTERNAL_ID = 3;
    const CREDIT_NOTES_LIMIT = 5;
    const INVOICE_ATTACHMENTS_LIMIT = 5;
    const INVOICE_TEMPLATE_DEFAULT = 'SzlaMost';
    const INVOICE_TEMPLATE_TRADITIONAL = 'SzlaAlap';
    const INVOICE_TEMPLATE_ENV_FRIENDLY = 'SzlaNoEnv';
    const INVOICE_TEMPLATE_8CM = 'Szla8cm';
    const INVOICE_TEMPLATE_RETRO = 'SzlaTomb';

    private InvoiceHeader $header;

    protected $seller;
    protected $buyer;
    protected $waybill;
    protected $items = [];
    protected $creditNotes = [];
    protected $additive = true;
    protected $attachments = [];

    public function __construct($type = self::INVOICE_TYPE_P_INVOICE)
    {
        if (! empty($type)) {
            $this->setHeader(new InvoiceHeader($type));
        }
    }

    public function getHeader(): InvoiceHeader
    {
        return $this->header;
    }

    public function setHeader(InvoiceHeader $header): static
    {
        $this->header = $header;

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

    public function setBuyer(Buyer $buyer)
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getWaybill()
    {
        return $this->waybill;
    }

    public function setWaybill(Waybill $waybill): static
    {
        $this->waybill = $waybill;

        return $this;
    }

    public function addItem(InvoiceItem $item): static
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

    public function addCreditNote(InvoiceCreditNote $creditNote): static
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

    public function setCreditNotes(array $creditNotes)
    {
        $this->creditNotes = $creditNotes;

        return $this;
    }

    public function isAdditive(): bool
    {
        return $this->additive;
    }

    public function setAdditive(bool $additive): static
    {
        $this->additive = $additive;

        return $this;
    }

    public function buildXmlData(SzamlaAgentRequest $request): array
    {
        switch ($request->getXmlName()) {
            case $request::XML_SCHEMA_CREATE_INVOICE:
                $data = $this->buildFieldsData($request, ['beallitasok', 'fejlec', 'elado', 'vevo', 'fuvarlevel', 'tetelek']);
                break;
            case $request::XML_SCHEMA_DELETE_PROFORMA:
                $data = $this->buildFieldsData($request, ['beallitasok', 'fejlec']);
                break;
            case $request::XML_SCHEMA_CREATE_REVERSE_INVOICE:
                $data = $this->buildFieldsData($request, ['beallitasok', 'fejlec', 'elado', 'vevo']);
                break;
            case $request::XML_SCHEMA_PAY_INVOICE:
                $data = $this->buildFieldsData($request, ['beallitasok']);
                $data = array_merge($data, $this->buildCreditsXmlData());
                break;
            case $request::XML_SCHEMA_REQUEST_INVOICE_XML:
            case $request::XML_SCHEMA_REQUEST_INVOICE_PDF:
                $settings = $this->buildFieldsData($request, ['beallitasok']);
                $data = $settings['beallitasok'];
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$request->getXmlName()}.");
        }

        return $data;
    }

    private function buildFieldsData(SzamlaAgentRequest $request, array $fields): array
    {
        $data = [];

        if (!empty($fields)) {
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
                    case 'elado':
                        $value = (SzamlaAgentUtil::isNotNull($this->getSeller()))
                            ? $this->getSeller()->buildXmlData($request)
                            : [];
                        break;
                    case 'vevo':
                        $value = (SzamlaAgentUtil::isNotNull($this->getBuyer()))
                            ? $this->getBuyer()->buildXmlData($request)
                            : [];
                        break;
                    case 'fuvarlevel':
                        $value = (SzamlaAgentUtil::isNotNull($this->getWaybill()))
                            ? $this->getWaybill()->buildXmlData($request)
                            : [];
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

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment($filePath)
    {
        if (empty($filePath)) {
            Log::writeLog("A csatolandó fájl neve nincs megadva!", Log::LOG_LEVEL_WARN);
        } else {
            if (count($this->attachments) >= self::INVOICE_ATTACHMENTS_LIMIT) {
                throw new SzamlaAgentException('A következő fájl csatolása sikertelen: "' . $filePath. '". Egy számlához maximum ' . self::INVOICE_ATTACHMENTS_LIMIT . ' fájl csatolható!');
            }

            if (!file_exists($filePath)) {
                throw new SzamlaAgentException(SzamlaAgentException::ATTACHMENT_NOT_EXISTS . ': '. $filePath);
            }
            $this->attachments[] = $filePath;
        }
    }
}
