<?php

namespace SzamlaAgent;

class CookieHandler
{
    const JSON_FILE_NAME = "cookies.json";
    const COOKIE_FILE_PATH = __DIR__ . "/../../storage/cookie/";
    const COOKIES_STORAGE_FILE = self::COOKIE_FILE_PATH . self::JSON_FILE_NAME;
    const COOKIE_HEADER_TEXT = "JSESSIONID=";
    const DEFAULT_COOKIE_JSON_CONTENT = "{}";
    const COOKIE_HANDLE_MODE_DEFAULT = 0;
    const COOKIE_HANDLE_MODE_JSON = 1;
    const COOKIE_FILENAME = 'cookie.txt';

    const COOKIE_HANDLE_MODE_DATABASE = 2;
    private $agent;
    private $cookieIdentifier;
    private $sessions = [];
    private $cookieSessionId = "";
    private $cookieHandleMode = self::COOKIE_HANDLE_MODE_DEFAULT;
    private $cookieFileName = self::COOKIE_FILENAME;

    public function __construct($agent)
    {
        $this->agent = $agent;
        $this->init();
    }

    private function init()
    {
        $this->cookieIdentifier = $this->createCookieIdentifier();
        $this->cookieFileName = $this->buildCookieFileName();
    }

    private function addSession($sessionId)
    {
        if (SzamlaAgentUtil::isNotNull($sessionId)) {
            $this->sessions[$this->cookieIdentifier]['sessionID'] = $sessionId;
            $this->sessions[$this->cookieIdentifier]['timestamp'] = time();
        }
    }

    public function handleSessionId($header)
    {
        $savedSessionId = [];
        preg_match_all('/(?<=JSESSIONID=)(.*?)(?=;)/', $header, $savedSessionId);

        if (isset($savedSessionId[0][0])) {
            $this->setCookieSessionId($savedSessionId[0][0]);
            if ($this->isHandleModeJson()) {
                $this->addSession($savedSessionId[0][0]);
            }
        }
    }

    public function saveSessions()
    {
        if ($this->isHandleModeJson()) {
            file_put_contents(self::COOKIES_STORAGE_FILE, json_encode($this->sessions));
        }
    }

    public function addCookieToHeader()
    {
        $this->refreshJsonSessionData();
        if (!empty($this->cookieSessionId)) {
            $this->agent->addCustomHTTPHeader('Cookie', self::COOKIE_HEADER_TEXT . $this->cookieSessionId);
        }
    }

    private function createCookieIdentifier()
    {
        $username = $this->agent->getUsername();
        $apiKey = $this->agent->getApiKey();
        $result = null;

        if (!empty($username)) {
            $result = hash('sha1', $username);

        } elseif (!empty($apiKey)) {
            $result = hash('sha1', $apiKey);
        }

        if (!$result || !SzamlaAgentUtil::isNotNull($result)) {
            $this->agent->writeLog("Cookie ID generation failed.", Log::LOG_LEVEL_WARN);
        }
        return $result;
    }

    private function checkCookieContainer()
    {
        if (!file_exists(self::COOKIES_STORAGE_FILE)) {
            file_put_contents(self::COOKIES_STORAGE_FILE, self::DEFAULT_COOKIE_JSON_CONTENT);
        }
    }

    private function initJsonSessionId()
    {
        $cookieFileContent = file_get_contents(self::COOKIES_STORAGE_FILE);
        $this->checkFileIsValidJson($cookieFileContent);
        $this->sessions = json_decode($cookieFileContent, true);
    }

    private function checkFileIsValidJson($cookieFileContent)
    {
        try {
            SzamlaAgentUtil::isValidJSON($cookieFileContent);
        } catch (SzamlaAgentException $e) {
            $this->agent->writeLog("The content of cookies.txt is invalid and has been deleted", Log::LOG_LEVEL_ERROR);
            file_put_contents(self::COOKIES_STORAGE_FILE, self::DEFAULT_COOKIE_JSON_CONTENT);
        }
    }

    public function isHandleModeDefault()
    {
        return $this->cookieHandleMode == self::COOKIE_HANDLE_MODE_DEFAULT;
    }

    public function isHandleModeJson()
    {
        return $this->cookieHandleMode == self::COOKIE_HANDLE_MODE_JSON;
    }

    public function isHandleModeDatabase()
    {
        return $this->cookieHandleMode == self::COOKIE_HANDLE_MODE_DATABASE;
    }

    public function isNotHandleModeDefault()
    {
        return $this->cookieHandleMode != self::COOKIE_HANDLE_MODE_DEFAULT;
    }

    public function isNotHandleModeJson()
    {
        return $this->cookieHandleMode != self::COOKIE_HANDLE_MODE_JSON;
    }

    public function isNotHandleModeDatabase()
    {
        return $this->cookieHandleMode != self::COOKIE_HANDLE_MODE_DATABASE;
    }

    public function getCookieHandleMode()
    {
        return $this->cookieHandleMode;
    }

    public function setCookieHandleMode($cookieHandleMode): static
    {
        $this->cookieHandleMode = $cookieHandleMode;

        return $this;
    }

    public function getCookieSessionId()
    {
        return $this->cookieSessionId;
    }

    public function setCookieSessionId($cookieSessionId): static
    {
        $this->cookieSessionId = $cookieSessionId;

        return $this;
    }

    private function refreshJsonSessionData()
    {
        if ($this->isHandleModeJson()) {
            $this->checkCookieContainer();
            $this->initJsonSessionId();
            if (isset($this->sessions[$this->cookieIdentifier])) {
                $this->cookieSessionId = $this->sessions[$this->cookieIdentifier]['sessionID'];
            }
        }
    }

    public function getCookieFileName()
    {
        return $this->cookieFileName;
    }

    public function setCookieFileName($cookieFile): static
    {
        $this->cookieFileName = $cookieFile;

        return $this;
    }

    public function buildCookieFileName()
    {
        $fileName = 'cookie';

        return $fileName . '_' . $this->cookieIdentifier . '.txt';
    }

    public function getCookieFilePath()
    {
        $fileName = $this->getCookieFileName();
        if (SzamlaAgentUtil::isBlank($fileName)) {
            $fileName = CookieHandler::COOKIE_FILENAME;
        }

        return SzamlaAgentUtil::getBasePath() . $fileName;
    }

    public function getDefaultCookieFile()
    {
        return $this->getCookieFilePath();
    }

    public function checkCookieFile($cookieFile)
    {
        if (file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile), 'curl') === false) {
            file_put_contents($cookieFile, "");
            $this->agent->writeLog("The content of the cookie file has changed.", Log::LOG_LEVEL_DEBUG);
        }
    }

    public function isUsableCookieFile($cookieFile)
    {
        return file_exists($cookieFile) && filesize($cookieFile) > 0;
    }
}
