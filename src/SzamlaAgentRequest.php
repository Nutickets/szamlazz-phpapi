<?php

namespace SzamlaAgent;

use CURLFile;
use SzamlaAgent\Document\Document;

class SzamlaAgentRequest
{
    const HTTP_OK = 200;
    const CRLF = "\r\n";
    const XML_BASE_URL = 'http://www.szamlazz.hu/';
    const REQUEST_TIMEOUT = 30;
    const CALL_METHOD_LEGACY = 1;
    const CALL_METHOD_CURL = 2;
    const CALL_METHOD_AUTO = 3;
    const XML_SCHEMA_CREATE_INVOICE = 'xmlszamla';
    const XML_SCHEMA_CREATE_REVERSE_INVOICE = 'xmlszamlast';
    const XML_SCHEMA_PAY_INVOICE = 'xmlszamlakifiz';
    const XML_SCHEMA_REQUEST_INVOICE_XML = 'xmlszamlaxml';
    const XML_SCHEMA_REQUEST_INVOICE_PDF = 'xmlszamlapdf';
    const XML_SCHEMA_CREATE_RECEIPT = 'xmlnyugtacreate';
    const XML_SCHEMA_CREATE_REVERSE_RECEIPT = 'xmlnyugtast';
    const XML_SCHEMA_SEND_RECEIPT = 'xmlnyugtasend';
    const XML_SCHEMA_GET_RECEIPT = 'xmlnyugtaget';
    const XML_SCHEMA_TAXPAYER = 'xmltaxpayer';
    const XML_SCHEMA_DELETE_PROFORMA = 'xmlszamladbkdel';
    const REQUEST_AUTHORIZATION_BASIC_AUTH = 1;

    private SzamlaAgent $agent;
    private $type;
    private $entity;
    private $xmlData;
    private $xmlName;
    private $xmlFilePath;
    private $xsdDir;
    private $fileName;
    private $delim;
    private $postFields;
    private $cData = true;
    private $requestTimeout;
    private $cookieHandler;

    public function __construct(SzamlaAgent $agent, $type, $entity)
    {
        $this->setAgent($agent);
        $this->setType($type);
        $this->setEntity($entity);
        $this->setCData(true);
        $this->setRequestTimeout($agent->getRequestTimeout());
    }

    private function buildXmlData()
    {
        $this->setXmlFileData($this->getType());
        $agent = $this->getAgent();
        $agent->writeLog("XML adatok összeállítása elkezdődött.", Log::LOG_LEVEL_DEBUG);
        $xmlData = $this->getEntity()->buildXmlData($this);

        $xml = new SimpleXMLExtended($this->getXmlBase());
        $this->arrayToXML($xmlData, $xml);
        try {
            $result = SzamlaAgentUtil::checkValidXml($xml->saveXML());
            if (!empty($result)) {
                throw new SzamlaAgentException(SzamlaAgentException::XML_NOT_VALID . " a {$result[0]->line}. sorban: {$result[0]->message}. ");
            }
            $formatXml = SzamlaAgentUtil::formatXml($xml);
            $this->setXmlData($formatXml->saveXML());
            // Ha nincs hiba az XML-ben, elmentjük
            $agent->writeLog("The creation of XML data is complete.", Log::LOG_LEVEL_DEBUG);
            if (($agent->isXmlFileSave() && $agent->isRequestXmlFileSave()) || version_compare(PHP_VERSION, '7.4.1') <= 0) {
                $this->createXmlFile($formatXml);
            }
        } catch (\Exception $e) {
            try {
                $formatXml = SzamlaAgentUtil::formatXml($xml);
                $this->setXmlData($formatXml->saveXML());
                if (!empty($this->getXmlData())) {
                    $xmlData = $this->getXmlData();
                }
            } catch (\Exception $ex) {
                // ha az adatok alapján nem állítható össze az XML, továbblépünk és naplózzuk az eredetileg beállított XML adatokat
            }
            $agent->writeLog(print_r($xmlData, true), Log::LOG_LEVEL_DEBUG);
            throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_BUILD_FAILED . ":  {$e->getMessage()} ");
        }
    }

    private function arrayToXML(array $xmlData, SimpleXMLExtended &$xmlFields)
    {
        foreach ($xmlData as $key => $value) {
            if (is_array($value)) {
                $fieldKey = $key;
                if (strpos($key, "item") !== false) {
                    $fieldKey = 'tetel';
                }
                if (strpos($key, "note") !== false) {
                    $fieldKey = 'kifizetes';
                }
                $subNode = $xmlFields->addChild("$fieldKey");
                $this->arrayToXML($value, $subNode);
            } else {
                if (is_bool($value)) {
                    $value = ($value) ? 'true' : 'false';
                } elseif (!$this->isCData()) {
                    $value = htmlspecialchars("$value");
                }

                if ($this->isCData()) {
                    $xmlFields->addChildWithCData("$key", $value);
                } else {
                    $xmlFields->addChild("$key", $value);
                }
            }
        }
    }

    private function createXmlFile(\DOMDocument $xml)
    {
        $fileName = SzamlaAgentUtil::getXmlFileName('request', $this->getXmlName(), $this->getEntity());
        $xml->save($fileName);

        $this->setXmlFilePath(SzamlaAgentUtil::getRealPath($fileName));
        $this->getAgent()->writeLog("XML fájl mentése sikeres: " . SzamlaAgentUtil::getRealPath($fileName), Log::LOG_LEVEL_DEBUG);
    }

    private function getXmlBase()
    {
        $xmlName = $this->getXmlName();

        $queryData = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $queryData .= '<' . $xmlName . ' xmlns="' . $this->getXmlNs($xmlName) . '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="' . $this->getSchemaLocation($xmlName) . '">' . PHP_EOL;
        $queryData .= '</' . $xmlName . '>' . self::CRLF;

        return $queryData;
    }

    private function getSchemaLocation($xmlName)
    {
        return self::XML_BASE_URL . "szamla/{$xmlName} http://www.szamlazz.hu/szamla/docs/xsds/{$this->getXsdDir()}/{$xmlName}.xsd";
    }

    private function getXmlNs($xmlName)
    {
        return self::XML_BASE_URL . "{$xmlName}";
    }

    private function buildQuery()
    {
        $this->setDelim(substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16));

        $queryData  = '--' . $this->getDelim() . self::CRLF;
        $queryData .= 'Content-Disposition: form-data; name="' . $this->getFileName() . '"; filename="' . $this->getFileName() . '"' . self::CRLF;
        $queryData .= 'Content-Type: text/xml' . self::CRLF . self::CRLF;
        $queryData .= $this->getXmlData() . self::CRLF;
        $queryData .= "--" . $this->getDelim() . "--" . self::CRLF;

        $this->setPostFields($queryData);
    }

    private function setXmlFileData($type)
    {
        switch ($type) {
            // Számlakészítés (normál, előleg, végszámla)
            case 'generateProforma':
            case 'generateInvoice':
            case 'generatePrePaymentInvoice':
            case 'generateFinalInvoice':
            case 'generateCorrectiveInvoice':
            case 'generateDeliveryNote':
                $fileName = 'action-xmlagentxmlfile';
                $xmlName = self::XML_SCHEMA_CREATE_INVOICE;
                $xsdDir = 'agent';
                break;
            // Számla sztornó
            case 'generateReverseInvoice':
                $fileName = 'action-szamla_agent_st';
                $xmlName = self::XML_SCHEMA_CREATE_REVERSE_INVOICE;
                $xsdDir  = 'agentst';
                break;
            // Jóváírás rögzítése
            case 'payInvoice':
                $fileName = 'action-szamla_agent_kifiz';
                $xmlName = self::XML_SCHEMA_PAY_INVOICE;
                $xsdDir = 'agentkifiz';
                break;
            // Számla adatok lekérése
            case 'requestInvoiceData':
                $fileName = 'action-szamla_agent_xml';
                $xmlName = self::XML_SCHEMA_REQUEST_INVOICE_XML;
                $xsdDir = 'agentxml';
                break;
            // Számla PDF lekérése
            case 'requestInvoicePDF':
                $fileName = 'action-szamla_agent_pdf';
                $xmlName = self::XML_SCHEMA_REQUEST_INVOICE_PDF;
                $xsdDir = 'agentpdf';
                break;
            // Nyugta készítés
            case 'generateReceipt':
                $fileName = 'action-szamla_agent_nyugta_create';
                $xmlName = self::XML_SCHEMA_CREATE_RECEIPT;
                $xsdDir = 'nyugtacreate';
                break;
            // Nyugta sztornó
            case 'generateReverseReceipt':
                $fileName = 'action-szamla_agent_nyugta_storno';
                $xmlName = self::XML_SCHEMA_CREATE_REVERSE_RECEIPT;
                $xsdDir = 'nyugtast';
                break;
            // Nyugta kiküldés
            case 'sendReceipt':
                $fileName = 'action-szamla_agent_nyugta_send';
                $xmlName = self::XML_SCHEMA_SEND_RECEIPT;
                $xsdDir = 'nyugtasend';
                break;
            // Nyugta adatok lekérése
            case 'requestReceiptData':
            case 'requestReceiptPDF':
                $fileName = 'action-szamla_agent_nyugta_get';
                $xmlName = self::XML_SCHEMA_GET_RECEIPT;
                $xsdDir = 'nyugtaget';
                break;
            // Adózó adatainak lekérdezése
            case 'getTaxPayer':
                $fileName = 'action-szamla_agent_taxpayer';
                $xmlName = self::XML_SCHEMA_TAXPAYER;
                $xsdDir = 'taxpayer';
                break;
            // Díjbekérő törlése
            case 'deleteProforma':
                $fileName = 'action-szamla_agent_dijbekero_torlese';
                $xmlName = self::XML_SCHEMA_DELETE_PROFORMA;
                $xsdDir = 'dijbekerodel';
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::REQUEST_TYPE_NOT_EXISTS . ": {$type}");
        }

        $this->setFileName($fileName);
        $this->setXmlName($xmlName);
        $this->setXsdDir($xsdDir);
    }

    public function getConnectionModeName($type)
    {
        return match ($type) {
            self::CALL_METHOD_CURL => 'CURL',
            self::CALL_METHOD_LEGACY => 'LEGACY',
            default => throw new SzamlaAgentException(SzamlaAgentException::CONNECTION_METHOD_CANNOT_BE_DETERMINED),
        };
    }

    public function send()
    {
        $this->buildXmlData();
        $this->buildQuery();

        $method = $this->agent->getCallMethod();
        $response = match ($method) {
            self::CALL_METHOD_AUTO => $this->checkConnection(),
            self::CALL_METHOD_CURL => $this->makeCurlCall(),
            self::CALL_METHOD_LEGACY => $this->makeLegacyCall(),
            default => throw new SzamlaAgentException(SzamlaAgentException::CALL_TYPE_NOT_EXISTS . ": {$method}"),
        };

        $this->checkXmlFileSave();

        return $response;
    }

    private function checkConnection()
    {
        $agent = $this->getAgent();

        $ch = curl_init($agent->getApiUrl());
        // setting ssl verification ok, definiing root certificate to validate connection to remote server
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // never ever set to false, as this carries huge security risk!!!
        // if you experience problems with ssl validation, use legacy data call instead! (see below)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, $agent->getCertificationFile());
        curl_setopt($ch, CURLOPT_NOBODY, true);

        if ($this->isBasicAuthRequest()) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->getBasicAuthUserPwd());
        }

        curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code == self::HTTP_OK) {
            $agent->setCallMethod(self::CALL_METHOD_CURL);
            $agent->writeLog("A kapcsolódás típusa beállítva a következőre: CURL.", Log::LOG_LEVEL_DEBUG);
            return $this->makeCurlCall();
        } else {
            $agent->setCallMethod(self::CALL_METHOD_LEGACY);
            $agent->writeLog("A kapcsolódás típusa beállítva a következőre: LEGACY, mert a CURL nem használható.", Log::LOG_LEVEL_WARN);
            return $this->makeLegacyCall();
        }
    }

    private function makeCurlCall()
    {
        $agent = $this->getAgent();
        $cookieHandler = $agent->getCookieHandler();

        $ch = curl_init($agent->getApiUrl());

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, $agent->getCertificationFile());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        if ($this->isBasicAuthRequest()) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->getBasicAuthUserPwd());
        }

        $mimeType = 'text/xml';
        if (($agent->isXmlFileSave() && $agent->isRequestXmlFileSave()) || version_compare(PHP_VERSION, '7.4.1') <= 0) {
            $xmlFile = new CURLFile($this->getXmlFilePath(), $mimeType, basename($this->getXmlFilePath()));
        } else {
            $xmlContent = 'data://application/octet-stream;base64,' . base64_encode($this->getXmlData());
            $fileName = SzamlaAgentUtil::getXmlFileName('request', $this->getXmlName(), $this->getEntity());
            $xmlFile = new CURLFile($xmlContent, $mimeType, basename($fileName));
        }

        $postFields = [$this->getFileName() => $xmlFile];

        $httpHeaders = [
            'charset: ' . SzamlaAgent::CHARSET,
            'PHP: ' . PHP_VERSION,
            'API: ' . SzamlaAgent::API_VERSION
        ];

        if ($cookieHandler->isNotHandleModeDefault()) {
            $cookieHandler->addCookieToHeader();
        } else {
            /** @var CookieHandler $cookieHandler */
            $cookieFile = $cookieHandler->getDefaultCookieFile();
            $cookieHandler->checkCookieFile($cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            if ($cookieHandler->isUsableCookieFile($cookieFile)) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
        }

        $customHttpHeaders = $agent->getCustomHTTPHeaders();
        if (!empty($customHttpHeaders)) {
            foreach ($customHttpHeaders as $key => $value) {
                $httpHeaders[] = $key . ': ' . $value;
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

        if ($this->isAttachments()) {
            $attachments = $this->getEntity()->getAttachments();
            if (!empty($attachments)) {
                for ($i = 0; $i < count($attachments); $i++) {
                    $attachCount = ($i + 1);
                    if (file_exists($attachments[$i])) {
                        $isAttachable = true;
                        foreach ($postFields as $field) {
                            if ($field->name === $attachments[$i]) {
                                $isAttachable = false;
                                $agent->writeLog($attachCount . ". számlamelléklet már csatolva van: " . $attachments[$i], Log::LOG_LEVEL_WARN);
                            }
                        }

                        if ($isAttachable) {
                            $attachment = new CURLFile($attachments[$i]);
                            $attachment->setPostFilename(basename($attachments[$i]));
                            $postFields["attachfile" . $attachCount] = $attachment;
                            $agent->writeLog($attachCount . ". számlamelléklet csatolva: " . $attachments[$i], Log::LOG_LEVEL_DEBUG);
                        }
                    }
                }
            }
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getRequestTimeout());

        $agent->writeLog("CURL adatok elküldése elkezdődött: " . $this->getPostFields(), Log::LOG_LEVEL_DEBUG);
        $result = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header     = substr($result, 0, $headerSize);
        $headers    = preg_split('/\n|\r\n?/', $header);
        $body       = substr($result, $headerSize);

        // Beállítjuk a session id-t ha kapunk újat
        $cookieHandler->handleSessionId($header);

        $response = [
            'headers' => $this->getHeadersFromResponse($headers),
            'body'    => $body
        ];

        $error = curl_error($ch);
        if (!empty($error)) {
            throw new SzamlaAgentException($error);
        } else {
            $keys = implode(",", array_keys($headers));
            if ($response['headers']['Content-Type'] == 'application/pdf' || (!preg_match('/(szlahu_)/', $keys, $matches))) {
                $msg = $response['headers'];
            } else {
                $msg = $response;
            }

            $response['headers']['Schema-Type'] = $this->getXmlSchemaType();
            $agent->writeLog("CURL adatok elküldése sikeresen befejeződött: " . print_r($msg, true), Log::LOG_LEVEL_DEBUG);
        }
        curl_close($ch);

        // JSON mód esetén mentjük a session-höz tartozó cookie adatokat
        if ($cookieHandler->isHandleModeJson()) {
            $cookieHandler->saveSessions();
        }
        return $response;
    }

    private function makeLegacyCall()
    {
        $agent = $this->getAgent();

        if ($this->isAttachments()) {
            throw new SzamlaAgentException(SzamlaAgentException::SENDING_ATTACHMENT_NOT_ALLOWED);
        }

        $cookieText = "";
        $cookies = [];
        $stored_cookies = [];

        $cookieFile = $this->agent->getCookieHandler()->getCookieFilePath();
        if (isset($cookieFile) && file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile), 'curl') === false) {
            $stored_cookies = unserialize(file_get_contents($cookieFile));
            $cookieText = "\r\n" . "Cookie: JSESSIONID=" . $stored_cookies["JSESSIONID"];
        }

        $httpHeaders = "Content-Type: multipart/form-data; boundary=".$this->getDelim().$cookieText."; charset= ".SzamlaAgent::CHARSET."; PHP= ".PHP_VERSION."; API= ".SzamlaAgent::API_VERSION;
        if ($this->isBasicAuthRequest()) {
            $httpHeaders.= "Authorization: Basic ". base64_encode($this->getBasicAuthUserPwd());
        }

        $customHttpHeaders = $agent->getCustomHTTPHeaders();
        if (!empty($customHttpHeaders)) {
            foreach ($customHttpHeaders as $key => $value) {
                $httpHeaders .= "; " . $key . "=" . $value;
            }
        }

        $context = stream_context_create(array(
            'http' => [
                "method" => "POST",
                "header" => $httpHeaders,
                "content" => $this->getPostFields()
            ]
        ));

        $agent->writeLog("LEGACY adatok elküldése elkezdődött: " . self::CRLF . $this->getPostFields(), Log::LOG_LEVEL_DEBUG);
        $body = file_get_contents($agent->getApiUrl(), false, $context);

        if (!empty($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^Set-Cookie:\s*([^;]+)/', $header, $matches)) {
                    parse_str($matches[1], $temp);
                    $cookies += $temp;
                }
            }
        }

        $response = [
            'headers' => (!empty($http_response_header) ? $this->getHeadersFromResponse($http_response_header) : []),
            'body'    => $body
        ];

        if (isset($response['headers']) && isset($response['headers']['Content-Type']) && $response['headers']['Content-Type'] == 'application/pdf') {
            $msg = $response['headers'];
        } else {
            $msg = $response;
        }
        $response['headers']['Schema-Type'] = $this->getXmlSchemaType();
        $agent->writeLog("LEGACY adatok elküldése befejeződött: " . print_r($msg, true), Log::LOG_LEVEL_DEBUG);

        if (isset($cookieFile) && isset($cookies['JSESSIONID'])) {
            if (file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile), 'curl') !== false) {
                file_put_contents($cookieFile, serialize($cookies));
                $agent->writeLog("Cookie tartalma megváltozott.", Log::LOG_LEVEL_DEBUG);
            } elseif (file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile), 'curl') === false && ($stored_cookies != $cookies)) {
                file_put_contents($cookieFile, serialize($cookies));
                $agent->writeLog("Cookie tartalma megváltozott.", Log::LOG_LEVEL_DEBUG);
            } elseif (file_exists($cookieFile) && filesize($cookieFile) == 0) {
                file_put_contents($cookieFile, serialize($cookies));
                $agent->writeLog("Cookie tartalma megváltozott.", Log::LOG_LEVEL_DEBUG);
            }
        }
        return $response;
    }

    private function getHeadersFromResponse($headerContent)
    {
        $headers = [];
        foreach ($headerContent as $index => $content) {
            if (SzamlaAgentUtil::isNotBlank($content)) {
                if ($index === 0) {
                    $headers['http_code'] = $content;
                } else {
                    $pos = strpos($content, ":");
                    if ($pos !== false) {
                        [$key, $value] = explode(': ', $content);
                        $headers[$key] = $value;
                    }
                }
            }
        }
        return $headers;
    }

    public function getAgent(): SzamlaAgent
    {
        return $this->agent;
    }

    private function setAgent(SzamlaAgent $agent)
    {
        $this->agent = $agent;
    }

    private function getType()
    {
        return $this->type;
    }

    private function setType($type)
    {
        $this->type = $type;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    private function setEntity($entity)
    {
        $this->entity = $entity;
    }

    private function getXmlData()
    {
        return $this->xmlData;
    }

    private function setXmlData($xmlData)
    {
        $this->xmlData = $xmlData;
    }

    private function getDelim()
    {
        return $this->delim;
    }

    private function setDelim($delim)
    {
        $this->delim = $delim;
    }

    private function getPostFields()
    {
        return $this->postFields;
    }

    private function setPostFields($postFields)
    {
        $this->postFields = $postFields;
    }

    private function isCData()
    {
        return $this->cData;
    }

    private function setCData($cData)
    {
        $this->cData = $cData;
    }

    public function getXmlName()
    {
        return $this->xmlName;
    }

    private function setXmlName($xmlName)
    {
        $this->xmlName = $xmlName;
    }

    private function getFileName()
    {
        return $this->fileName;
    }

    private function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function getXmlFilePath()
    {
        return $this->xmlFilePath;
    }

    private function setXmlFilePath($xmlFilePath)
    {
        $this->xmlFilePath = $xmlFilePath;
    }

    private function getXsdDir()
    {
        return $this->xsdDir;
    }

    private function setXsdDir($xsdDir)
    {
        $this->xsdDir = $xsdDir;
    }

    private function getXmlSchemaType()
    {
        return  match ($this->getXmlName()) {
            self::XML_SCHEMA_CREATE_INVOICE,
            self::XML_SCHEMA_CREATE_REVERSE_INVOICE,
            self::XML_SCHEMA_PAY_INVOICE,
            self::XML_SCHEMA_REQUEST_INVOICE_XML,
            self::XML_SCHEMA_REQUEST_INVOICE_PDF => Document::DOCUMENT_TYPE_INVOICE,

            self::XML_SCHEMA_DELETE_PROFORMA => Document::DOCUMENT_TYPE_PROFORMA,

            self::XML_SCHEMA_CREATE_RECEIPT,
            self::XML_SCHEMA_CREATE_REVERSE_RECEIPT,
            self::XML_SCHEMA_SEND_RECEIPT,
            self::XML_SCHEMA_GET_RECEIPT => Document::DOCUMENT_TYPE_RECEIPT,

            self::XML_SCHEMA_TAXPAYER => 'taxpayer',
            default => throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$this->getXmlName()}"),
        };
    }

    private function isAttachments()
    {
        $entity = $this->getEntity();
        if (is_a($entity, '\SzamlaAgent\Document\Invoice\Invoice')) {
            return (count($entity->getAttachments()) > 0);
        }

        return false;
    }

    private function isBasicAuthRequest()
    {
        $agent = $this->getAgent();

        return ($agent->hasEnvironment() && $agent->getEnvironmentAuthType() == self::REQUEST_AUTHORIZATION_BASIC_AUTH);
    }

    private function getBasicAuthUserPwd()
    {
        return $this->getAgent()->getEnvironmentAuthUser() . ":" . $this->getAgent()->getEnvironmentAuthPassword();
    }

    private function getRequestTimeout()
    {
        if ($this->requestTimeout == 0) {
            return self::REQUEST_TIMEOUT;
        }

        return $this->requestTimeout;
    }

    private function setRequestTimeout($timeout)
    {
        $this->requestTimeout = $timeout;
    }

    private function checkXmlFileSave()
    {
        if (($this->agent->isNotXmlFileSave() || $this->agent->isNotRequestXmlFileSave())) {
            try {
                $xmlData = SzamlaAgentUtil::isNotNull($this->getXmlFilePath()) ? $this->getXmlFilePath() : '';
                if (is_file($xmlData)) {
                    unlink($this->getXmlFilePath());
                }
            } catch (\Exception $e) {
                $this->agent->writeLog('XML fájl törlése sikertelen. ' . $e->getMessage(), Log::LOG_LEVEL_WARN);
            }
        }
    }
}
