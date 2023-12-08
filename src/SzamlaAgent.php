<?php

namespace SzamlaAgent;

use SzamlaAgent\Document\DeliveryNote;
use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\CorrectiveInvoice;
use SzamlaAgent\Document\Invoice\FinalInvoice;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Document\Invoice\PrePaymentInvoice;
use SzamlaAgent\Document\Invoice\ReverseInvoice;
use SzamlaAgent\Document\Proforma;
use SzamlaAgent\Document\Receipt\Receipt;
use SzamlaAgent\Document\Receipt\ReverseReceipt;
use SzamlaAgent\Response\SzamlaAgentResponse;

class SzamlaAgent
{
    const API_VERSION = '2.10.13';
    const API_URL = 'https://www.szamlazz.hu/szamla/';
    const PHP_VERSION = '5.6';
    const CHARSET = 'utf-8';
    const CERTIFICATION_FILENAME = 'cacert.pem';
    const CERTIFICATION_PATH = '/cert';
    const PDF_FILE_SAVE_PATH = '/pdf';
    const XML_FILE_SAVE_PATH = '/xmls';
    const ATTACHMENTS_SAVE_PATH = '/attachments';

    /**
     * 0: LOG_LEVEL_OFF
     * 1: LOG_LEVEL_ERROR
     * 2: LOG_LEVEL_WARN
     * 3: LOG_LEVEL_DEBUG
     */
    private $logLevel;
    private $logEmail = '';

    /**
     * 1: CALL_METHOD_LEGACY
     * 2: CALL_METHOD_CURL
     * 3: CALL_METHOD_AUTO
     */
    private $callMethod = SzamlaAgentRequest::CALL_METHOD_CURL;
    private SzamlaAgentSetting $setting;
    private $request;
    private $requestTimeout = SzamlaAgentRequest::REQUEST_TIMEOUT;
    private $response;
    protected static $agents = [];
    protected $customHTTPHeaders = [];
    protected $apiUrl = self::API_URL;
    protected $xmlFileSave = true;
    protected $requestXmlFileSave = true;
    protected $responseXmlFileSave = true;
    protected $pdfFileSave = true;
    protected $environment = [];
    private $certificationPath = self::CERTIFICATION_PATH;
    private $cookieHandler;

    public function __construct($username, $password, $apiKey, $downloadPdf, $logLevel = Log::LOG_LEVEL_DEBUG, $responseType = SzamlaAgentResponse::RESULT_AS_TEXT, $aggregator = '')
    {
        $this->setSetting(new SzamlaAgentSetting($username, $password, $apiKey, $downloadPdf, SzamlaAgentSetting::DOWNLOAD_COPIES_COUNT, $responseType, $aggregator));
        $this->setLogLevel($logLevel);
        $this->setCookieHandler(new CookieHandler($this));
        $this->writeLog("Account Agent initialization complete (" . (!empty($username) ? 'username: ' . $username : 'apiKey: ' . $apiKey) . ").", Log::LOG_LEVEL_DEBUG);
    }

    public static function create($username, $password, $downloadPdf = true, $logLevel = Log::LOG_LEVEL_DEBUG)
    {
        return resolve(static::class, [$username, $password, null, $downloadPdf, $logLevel]);
    }

    public function __destruct()
    {
        $this->writeLog("Account Agent operations completed." . PHP_EOL . str_repeat("_", 80) . PHP_EOL, Log::LOG_LEVEL_DEBUG);
    }

    public static function get($instanceId)
    {
        $index = self::getHash($instanceId);
        $agent = self::$agents[$index];

        if ($agent === null) {
            if (!str_contains($instanceId, '@') && strlen($instanceId) == SzamlaAgentSetting::API_KEY_LENGTH) {
                throw new SzamlaAgentException(SzamlaAgentException::NO_AGENT_INSTANCE_WITH_APIKEY);
            } else {
                throw new SzamlaAgentException(SzamlaAgentException::NO_AGENT_INSTANCE_WITH_USERNAME);
            }
        }
        return $agent;
    }

    protected static function getHash($username)
    {
        return hash('sha1', $username);
    }

    private function sendRequest(SzamlaAgentRequest $request)
    {
        $this->setRequest($request);
        $response = new SzamlaAgentResponse($this, $request->send());

        return $response->handleResponse();
    }

    public function generateDocument($type, Document $document)
    {
        $request = new SzamlaAgentRequest($this, $type, $document);
        return $this->sendRequest($request);
    }

    public function generateInvoice(Invoice $invoice)
    {
        return $this->generateDocument('generateInvoice', $invoice);
    }

    public function generatePrePaymentInvoice(PrePaymentInvoice $invoice)
    {
        return $this->generateInvoice($invoice);
    }

    public function generateFinalInvoice(FinalInvoice $invoice)
    {
        return $this->generateInvoice($invoice);
    }

    public function generateCorrectiveInvoice(CorrectiveInvoice $invoice)
    {
        return $this->generateInvoice($invoice);
    }

    public function generateReceipt(Receipt $receipt)
    {
        return $this->generateDocument('generateReceipt', $receipt);
    }

    public function payInvoice(Invoice $invoice)
    {
        if ($this->getResponseType() != SzamlaAgentResponse::RESULT_AS_TEXT) {
            $msg = 'Incorrect setting attempt when sending invoice payment data: the response version to the request must be in TEXT format!';
            $this->writeLog($msg, Log::LOG_LEVEL_WARN);
        }
        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_TEXT);
        return $this->generateDocument('payInvoice', $invoice);
    }

    public function sendReceipt(Receipt $receipt)
    {
        return $this->generateDocument('sendReceipt', $receipt);
    }

    public function getInvoiceData($data, $type = Invoice::FROM_INVOICE_NUMBER, $downloadPdf = false)
    {
        $invoice = new Invoice();

        if ($type == Invoice::FROM_INVOICE_NUMBER) {
            $invoice->getHeader()->setInvoiceNumber($data);
        } else {
            $invoice->getHeader()->setOrderNumber($data);
        }

        if ($this->getResponseType() !== SzamlaAgentResponse::RESULT_AS_XML) {
            $msg = 'Helytelen beállítási kísérlet a számla adatok lekérdezésénél: Számla adatok letöltéséhez a kérésre adott válasznak xml formátumúnak kell lennie!';
            $this->writeLog($msg, Log::LOG_LEVEL_WARN);
        }

        $this->setDownloadPdf($downloadPdf);
        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);

        return $this->generateDocument('requestInvoiceData', $invoice);
    }

    public function getInvoicePdf($data, $type = Invoice::FROM_INVOICE_NUMBER)
    {
        $invoice = new Invoice();

        if ($type == Invoice::FROM_INVOICE_NUMBER) {
            $invoice->getHeader()->setInvoiceNumber($data);
        } elseif ($type == Invoice::FROM_INVOICE_EXTERNAL_ID) {
            if (SzamlaAgentUtil::isBlank($data)) {
                throw new SzamlaAgentException(SzamlaAgentException::INVOICE_EXTERNAL_ID_IS_EMPTY);
            }
            $this->getSetting()->setInvoiceExternalId($data);
        } else {
            $invoice->getHeader()->setOrderNumber($data);
        }

        if (!$this->isDownloadPdf()) {
            $msg = 'Helytelen beállítási kísérlet a számla PDF lekérdezésénél: Számla letöltéshez a "downloadPdf" paraméternek "true"-nak kell lennie!';
            $this->writeLog($msg, Log::LOG_LEVEL_WARN);
        }
        $this->setDownloadPdf(true);
        return $this->generateDocument('requestInvoicePDF', $invoice);
    }

    public function isExistsInvoiceByExternalId($invoiceExternalId)
    {
        try {
            $result = $this->getInvoicePdf($invoiceExternalId, Invoice::FROM_INVOICE_EXTERNAL_ID);
            if ($result->isSuccess() && SzamlaAgentUtil::isNotBlank($result->getDocumentNumber())) {
                return true;
            }
        } catch (\Exception $e) {
            //
        }

        return false;
    }

    public function getReceiptData($receiptNumber)
    {
        return $this->generateDocument('requestReceiptData', new Receipt($receiptNumber));
    }

    public function getReceiptPdf($receiptNumber)
    {
        return $this->generateDocument('requestReceiptPDF', new Receipt($receiptNumber));
    }

    public function getTaxPayer($taxPayerId)
    {
        $request  = new SzamlaAgentRequest($this, 'getTaxPayer', new TaxPayer($taxPayerId));
        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_TAXPAYER_XML);
        return $this->sendRequest($request);
    }

    public function generateReverseInvoice(ReverseInvoice $invoice)
    {
        return $this->generateDocument('generateReverseInvoice', $invoice);
    }

    public function generateReverseReceipt(ReverseReceipt $receipt)
    {
        return $this->generateDocument('generateReverseReceipt', $receipt);
    }

    public function generateProforma(Proforma $proforma)
    {
        return $this->generateDocument('generateProforma', $proforma);
    }

    public function getDeleteProforma($data, $type = Proforma::FROM_INVOICE_NUMBER)
    {
        $proforma = new Proforma();

        if ($type == Proforma::FROM_INVOICE_NUMBER) {
            $proforma->getHeader()->setInvoiceNumber($data);
        } else {
            $proforma->getHeader()->setOrderNumber($data);
        }

        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);
        $this->setDownloadPdf(false);

        return $this->generateDocument('deleteProforma', $proforma);
    }

    public function generateDeliveryNote(DeliveryNote $deliveryNote)
    {
        return $this->generateDocument('generateDeliveryNote', $deliveryNote);
    }

    public function writeLog($message, $type = Log::LOG_LEVEL_DEBUG)
    {
        if ($this->logLevel < $type) {
            return false;
        }

        if ($this->logLevel != Log::LOG_LEVEL_OFF) {
            Log::writeLog($message, $type, $this->logEmail);
        }
        return true;
    }

    public function logError($message)
    {
        $this->writeLog($message, Log::LOG_LEVEL_ERROR);
    }

    public function getApiVersion()
    {
        return self::API_VERSION;
    }

    public function getLogLevel()
    {
        return $this->logLevel;
    }

    public function setLogLevel($logLevel): static
    {
        if (Log::isNotValidLogLevel($logLevel)) {
            $logLevel = Log::LOG_LEVEL_DEBUG;
        }

        $this->logLevel = $logLevel;

        return $this;
    }

    public function getCallMethod()
    {
        return $this->callMethod;
    }

    public function setCallMethod($callMethod): static
    {
        $this->callMethod = $callMethod;

        return $this;
    }

    public function getLogEmail()
    {
        return $this->logEmail;
    }

    public function setLogEmail($logEmail): static
    {
        $this->logEmail = $logEmail;

        return $this;
    }

    public function getCertificationFileName()
    {
        return self::CERTIFICATION_FILENAME;
    }

    public function getCertificationFile()
    {
        if ($this->getCertificationPath() == self::CERTIFICATION_PATH) {
            return SzamlaAgentUtil::getAbsPath(self::CERTIFICATION_PATH, $this->getCertificationFileName());
        } else {
            return $this->getCertificationPath() . DIRECTORY_SEPARATOR . $this->getCertificationFileName();
        }
    }

    public function getCertificationPath()
    {
        return $this->certificationPath;
    }

    public function setCertificationPath($certificationPath): static
    {
        $this->certificationPath = $certificationPath;

        return $this;
    }

    public function getCookieFileName()
    {
        return $this->cookieHandler->getCookieFileName();
    }

    public function setCookieFileName($cookieFile): static
    {
        $this->cookieHandler->setCookieFileName($cookieFile);

        return $this;
    }

    public function getSetting()
    {
        return $this->setting;
    }

    public function setSetting($setting): static
    {
        $this->setting = $setting;

        return $this;
    }

    public static function getAgents()
    {
        return self::$agents;
    }

    public function getUsername()
    {
        return $this->getSetting()->getUsername();
    }

    public function setUsername($username): static
    {
        $this->getSetting()->setUsername($username);

        return $this;
    }

    public function getPassword()
    {
        return $this->getSetting()->getPassword();
    }

    public function setPassword($password): static
    {
        $this->getSetting()->setPassword($password);

        return $this;
    }

    public function getApiKey()
    {
        return $this->getSetting()->getApiKey();
    }

    public function setApiKey($apiKey): static
    {
        $this->getSetting()->setApiKey($apiKey);

        return $this;
    }

    public function getApiUrl()
    {
        if (SzamlaAgentUtil::isNotBlank($this->getEnvironmentUrl())) {
            $this->setApiUrl($this->getEnvironmentUrl());
        } elseif (SzamlaAgentUtil::isBlank($this->apiUrl)) {
            $this->setApiUrl(self::API_URL);
        }

        return $this->apiUrl;
    }

    public function setApiUrl($apiUrl): static
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    public function isDownloadPdf()
    {
        return $this->getSetting()->isDownloadPdf();
    }

    public function setDownloadPdf($downloadPdf): static
    {
        $this->getSetting()->setDownloadPdf($downloadPdf);

        return $this;
    }

    public function getDownloadCopiesCount()
    {
        return $this->getSetting()->getDownloadCopiesCount();
    }

    public function setDownloadCopiesCount($downloadCopiesCount): static
    {
        $this->getSetting()->setDownloadCopiesCount($downloadCopiesCount);

        return $this;
    }

    public function getResponseType()
    {
        return $this->getSetting()->getResponseType();
    }

    public function setResponseType($responseType): static
    {
        $this->getSetting()->setResponseType($responseType);

        return $this;
    }

    public function getAggregator()
    {
        return $this->getSetting()->getAggregator();
    }

    public function setAggregator($aggregator): static
    {
        $this->getSetting()->setAggregator($aggregator);

        return $this;
    }

    public function getGuardian()
    {
        return $this->getSetting()->getGuardian();
    }

    public function setGuardian($guardian): static
    {
        $this->getSetting()->setGuardian($guardian);

        return $this;
    }

    public function getInvoiceExternalId()
    {
        return $this->getSetting()->getInvoiceExternalId();
    }

    public function setInvoiceExternalId($invoiceExternalId): static
    {
        $this->getSetting()->setInvoiceExternalId($invoiceExternalId);

        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request): static
    {
        $this->request = $request;

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response): static
    {
        $this->response = $response;

        return $this;
    }

    public function getLog()
    {
        return Log::get();
    }

    public function getCustomHTTPHeaders()
    {
        return $this->customHTTPHeaders;
    }

    public function addCustomHTTPHeader($key, $value)
    {
        if (SzamlaAgentUtil::isNotBlank($key)) {
            $this->customHTTPHeaders[$key] = $value;
        } else {
            $this->writeLog('Egyedi HTTP fejléchez megadott kulcs nem lehet üres', Log::LOG_LEVEL_WARN);
        }
    }

    public function removeCustomHTTPHeader($key)
    {
        if (SzamlaAgentUtil::isNotBlank($key)) {
            unset($this->customHTTPHeaders[$key]);
        }
    }

    public function isPdfFileSave()
    {
        return $this->pdfFileSave;
    }

    public function setPdfFileSave($pdfFileSave): static
    {
        $this->pdfFileSave = $pdfFileSave;

        return $this;
    }

    public function isXmlFileSave()
    {
        return $this->xmlFileSave;
    }

    public function isNotXmlFileSave()
    {
        return !$this->isXmlFileSave();
    }

    public function setXmlFileSave($xmlFileSave): static
    {
        $this->xmlFileSave = $xmlFileSave;

        return $this;
    }

    public function isRequestXmlFileSave()
    {
        return $this->requestXmlFileSave;
    }

    public function isNotRequestXmlFileSave()
    {
        return !$this->isRequestXmlFileSave();
    }

    public function setRequestXmlFileSave($requestXmlFileSave): static
    {
        $this->requestXmlFileSave = $requestXmlFileSave;

        return $this;
    }

    public function isResponseXmlFileSave()
    {
        return $this->responseXmlFileSave;
    }

    public function setResponseXmlFileSave($responseXmlFileSave): static
    {
        $this->responseXmlFileSave = $responseXmlFileSave;

        return $this;
    }

    public function getRequestEntity()
    {
        return $this->getRequest()->getEntity();
    }

    public function getRequestEntityHeader()
    {
        $header = null;

        $request = $this->getRequest();
        $entity = $request->getEntity();

        if ($entity != null && $entity instanceof Invoice) {
            $header = $entity->getHeader();
        }
        return $header;
    }

    public function getRequestTimeout()
    {
        return $this->requestTimeout;
    }

    public function setRequestTimeout($timeout): static
    {
        $this->requestTimeout = $timeout;

        return $this;
    }

    public function isInvoiceItemIdentifier()
    {
        return $this->getSetting()->isInvoiceItemIdentifier();
    }

    public function setInvoiceItemIdentifier($invoiceItemIdentifier): static
    {
        $this->getSetting()->setInvoiceItemIdentifier($invoiceItemIdentifier);

        return $this;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function hasEnvironment()
    {
        return ($this->environment != null && is_array($this->environment) && !empty($this->environment));
    }

    public function getEnvironmentName()
    {
        return ($this->hasEnvironment() && array_key_exists('name', $this->environment) ? $this->environment['name'] : null);
    }

    public function getEnvironmentUrl()
    {
        return ($this->hasEnvironment() && array_key_exists('url', $this->environment) ? $this->environment['url'] : null);
    }

    public function setEnvironment($name, $url, $authorization = []): static
    {
        $this->environment = [
           'name' => $name,
           'url'  => $url,
           'auth' => $authorization
        ];

        return $this;
    }

    public function hasEnvironmentAuth()
    {
        return $this->hasEnvironment() && array_key_exists('auth', $this->environment) && is_array($this->environment['auth']);
    }

    public function getEnvironmentAuthType()
    {
        return ($this->hasEnvironmentAuth() && array_key_exists('type', $this->environment['auth']) ? $this->environment['auth']['type'] : 0);
    }

    public function getEnvironmentAuthUser()
    {
        return ($this->hasEnvironmentAuth() && array_key_exists('user', $this->environment['auth']) ? $this->environment['auth']['user'] : null);
    }

    public function getEnvironmentAuthPassword()
    {
        return ($this->hasEnvironmentAuth() && array_key_exists('password', $this->environment['auth']) ? $this->environment['auth']['password'] : null);
    }

    public function getCookieHandleMode()
    {
        return $this->cookieHandler->getCookieHandleMode();
    }

    public function setCookieHandleMode($cookieHandleMode): static
    {
        $this->cookieHandler->setCookieHandleMode($cookieHandleMode);

        return $this;
    }

    public function getCookieSessionId()
    {
        return $this->cookieHandler->getCookieSessionId();
    }

    public function setCookieSessionId($cookieSessionId): static
    {
        $this->cookieHandler->setCookieSessionId($cookieSessionId);

        return $this;
    }

    public function getCookieHandler()
    {
        return $this->cookieHandler;
    }

    protected function setCookieHandler($cookieHandler): static
    {
        $this->cookieHandler = $cookieHandler;

        return $this;
    }
}
