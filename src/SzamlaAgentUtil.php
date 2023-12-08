<?php

namespace SzamlaAgent;

use ReflectionClass;

class SzamlaAgentUtil
{
    const DEFAULT_ADDED_DAYS = 8;
    const DEFAULT_BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
    const DATE_FORMAT_DATE      = 'date';
    const DATE_FORMAT_DATETIME  = 'datetime';
    const DATE_FORMAT_TIMESTAMP = 'timestamp';

    private static $basePath = self::DEFAULT_BASE_PATH;

    public static function addDaysToDate($count, $date = null)
    {
        $newDate = self::getToday();

        if (!empty($date)) {
            $newDate = new \DateTime($date);
        }
        $newDate->modify("+{$count} day");

        return self::getDateStr($newDate);
    }

    public static function getDateStr(\DateTime $date, $format = self::DATE_FORMAT_DATE)
    {
        $result = match ($format) {
            self::DATE_FORMAT_DATE => $date->format('Y-m-d'),
            self::DATE_FORMAT_DATETIME => $date->format('Y-m-d H:i:s'),
            self::DATE_FORMAT_TIMESTAMP => $date->getTimestamp(),
            default => throw new SzamlaAgentException(SzamlaAgentException::DATE_FORMAT_NOT_EXISTS . ': ' . $format),
        };

        return $result;
    }

    public static function getToday()
    {
        return new \DateTime('now');
    }

    public static function getTodayStr()
    {
        $data = self::getToday();
        return $data->format('Y-m-d');
    }

    public static function isValidDate($date)
    {
        $parsedDate = \DateTime::createFromFormat('Y-m-d', $date);

        $lastErrors = \DateTime::getLastErrors();

        if ((is_array($lastErrors) && $lastErrors['warning_count'] > 0) || !checkdate($parsedDate->format("m"), $parsedDate->format("d"), $parsedDate->format("Y"))) {
            return false;
        }

        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $parsedDate->format('Y-m-d'))) {
            return false;
        }
        return true;
    }

    public static function isNotValidDate($date)
    {
        return !self::isValidDate($date);
    }

    public static function getXmlFileName($prefix, $name, $entity = null)
    {
        if (!empty($name) && !empty($entity)) {
            $name .= '-' . (new ReflectionClass($entity))->getShortName();
        }

        $fileName  = $prefix . '-' . strtolower($name) . '-' . self::getDateTimeWithMilliseconds() . '.xml';
        return self::getAbsPath(SzamlaAgent::XML_FILE_SAVE_PATH, $fileName);
    }

    public static function getDateTimeWithMilliseconds()
    {
        return now()->format('YmdHis');
    }

    public static function formatXml(\SimpleXMLElement $simpleXMLElement)
    {
        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($simpleXMLElement->asXML());

        return $xmlDocument;
    }

    public static function formatResponseXml($response)
    {
        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($response);

        return $xmlDocument;
    }

    public static function checkValidXml($xmlContent)
    {
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->loadXML($xmlContent);

        $result = libxml_get_errors();
        libxml_clear_errors();

        return $result;
    }

    public static function getRealPath($path)
    {
        if (file_exists($path)) {
            return realpath($path);
        } else {
            return $path;
        }
    }

    public static function getAbsPath($dir, $fileName = '')
    {
        $file = SzamlaAgentUtil::getBasePath() . $dir . DIRECTORY_SEPARATOR . $fileName;

        return self::getRealPath($file);
    }

    public static function getBasePath()
    {
        if (self::isBlank(self::$basePath)) {
            return self::getRealPath(self::DEFAULT_BASE_PATH);
        } else {
            return self::getRealPath(self::$basePath);
        }
    }

    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }

    public static function getXmlPath()
    {
        return SzamlaAgentUtil::getBasePath() . SzamlaAgent::XML_FILE_SAVE_PATH;
    }

    public static function getPdfPath()
    {
        return SzamlaAgentUtil::getBasePath() . SzamlaAgent::PDF_FILE_SAVE_PATH;
    }

    public static function getLogPath()
    {
        return SzamlaAgentUtil::getBasePath() . Log::LOG_PATH;
    }

    public static function getDefaultAttachmentPath($fileName)
    {
        return self::getRealPath(SzamlaAgentUtil::getBasePath() . SzamlaAgent::ATTACHMENTS_SAVE_PATH . DIRECTORY_SEPARATOR . $fileName);
    }

    public static function toJson($data)
    {
        return json_encode($data);
    }

    public static function toArray($data)
    {
        return json_decode(self::toJson($data), true);
    }

    public static function doubleFormat($value)
    {
        if (is_int($value)) {
            $value = doubleval($value);
        }

        if (is_double($value)) {
            $decimals = strlen(preg_replace('/[\d]+[\.]?/', '', $value, 1));
            if ($decimals == 0) {
                $value = number_format((float)$value, 1, '.', '');
            }
        } else {
            Log::writeLog("Helytelen típus! Double helyett " . gettype($value) . " típus ennél az értéknél: " . $value, Log::LOG_LEVEL_WARN);
        }
        return $value;
    }

    public static function isBlank($value)
    {
        return (is_null($value) || (is_string($value) && $value !== '0' && (empty($value) || trim($value) == '')));
    }

    public static function isNotBlank($value)
    {
        return !self::isBlank($value);
    }

    public static function checkStrField($field, $value, $required, $class)
    {
        $errorMsg = "";
        if (isset($value) && !is_string($value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem szöveg!";
        } elseif ($required && self::isBlank($value)) {
            $errorMsg = self::getRequiredFieldErrMsg($field);
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    public static function checkStrFieldWithRegExp($field, $value, $required, $class, $pattern)
    {
        $errorMsg = "";
        self::checkStrField($field, $value, $required, __CLASS__);

        if (!preg_match($pattern, $value)) {
            $errorMsg = "A(z) '{$field}' field value is incorrect!";
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    public static function checkIntField($field, $value, $required, $class)
    {
        $errorMsg = "";
        if (isset($value) && !is_int($value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem egész szám!";
        } elseif ($required && !is_numeric($value)) {
            $errorMsg = self::getRequiredFieldErrMsg($field);
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    public static function checkDoubleField($field, $value, $required, $class)
    {
        $errorMsg = "";
        if (isset($value) && !is_double($value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem double!";
        } elseif ($required && !is_numeric($value)) {
            $errorMsg = self::getRequiredFieldErrMsg($field);
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    public static function checkDateField($field, $value, $required, $class)
    {
        $errorMsg = "";
        if (isset($value) && self::isNotValidDate($value)) {
            if ($required) {
                $errorMsg = "A(z) '{$field}' kötelező mező, de nem érvényes dátumot tartalmaz!";
            } else {
                $errorMsg = "A(z) '{$field}' mező értéke nem dátum!";
            }
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    public static function checkBoolField($field, $value, $required, $class)
    {
        $errorMsg = "";
        if (isset($value) && is_bool($value) === false) {
            if ($required) {
                $errorMsg = "A(z) '{$field}' kötelező mező, de az értéke nem logikai!";
            } else {
                $errorMsg = "A(z) '{$field}' értéke nem logikai!";
            }
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    public static function getRequiredFieldErrMsg($field)
    {
        return "A(z) '{$field}' kötelező mező, de nincs beállítva az értéke!";
    }

    public static function isNotNull($value)
    {
        return (null !== $value);
    }

    public static function addChildArray(\SimpleXMLElement $xmlNode, $name, $data)
    {
        $node = $xmlNode->addChild($name);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                self::addChildArray($node, $key, $value);
            } else {
                $node->addChild($key, $value);
            }
        }
    }

    public static function removeNamespaces(\SimpleXMLElement $xmlNode)
    {
        $xmlString = $xmlNode->asXML();
        $cleanedXmlString = preg_replace('/(<\/|<)[a-z0-9]+:([a-z0-9]+[ =>])/i', '$1$2', $xmlString);
        $cleanedXmlNode = simplexml_load_string($cleanedXmlString);

        return $cleanedXmlNode;
    }

    public static function isValidJSON($string)
    {
        // decode the JSON data
        $result = json_decode($string);
        // switch and check possible JSON errors
        $error = match (json_last_error()) {
            JSON_ERROR_NONE => '',
            JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded.',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON.',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded.',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON.',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
            JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded.',
            JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded.',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given.',
            default => 'Unknown JSON error occured.',
        };

        if ($error !== '') {
            throw new SzamlaAgentException($error);
        }

        return $result;
    }

    public static function emptyXmlDir()
    {
        self::deleteFilesFromDir(realpath(self::getXmlPath()), 'xml');
    }

    public static function emptyPdfDir()
    {
        self::deleteFilesFromDir(realpath(self::getPdfPath()), 'pdf');
    }

    public static function emptyLogDir()
    {
        self::deleteFilesFromDir(realpath(self::getLogPath()), 'log');
    }

    protected static function deleteFilesFromDir($dir, $extension = null)
    {
        if (self::isNotBlank($dir) && is_dir($dir)) {
            $filter = (self::isNotBlank($extension) ? '*.' . $extension  : '*');
            $files = glob($dir . DIRECTORY_SEPARATOR . $filter);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
