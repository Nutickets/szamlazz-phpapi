<?php

namespace SzamlaAgent;

use ReflectionClass;

class Language
{
    const LANGUAGE_HU = 'hu';
    const LANGUAGE_EN = 'en';
    const LANGUAGE_DE = 'de';
    const LANGUAGE_IT = 'it';
    const LANGUAGE_RO = 'ro';
    const LANGUAGE_SK = 'sk';
    const LANGUAGE_HR = 'hr';
    const LANGUAGE_FR = 'fr';
    const LANGUAGE_ES = 'es';
    const LANGUAGE_CZ = 'cz';
    const LANGUAGE_PL = 'pl';

    protected static $availableLanguages = [
        self::LANGUAGE_HU, self::LANGUAGE_EN, self::LANGUAGE_DE, self::LANGUAGE_IT,
        self::LANGUAGE_RO, self::LANGUAGE_SK, self::LANGUAGE_HR, self::LANGUAGE_FR,
        self::LANGUAGE_ES, self::LANGUAGE_CZ, self::LANGUAGE_PL
    ];

    public static function getDefault()
    {
        return self::LANGUAGE_HU;
    }

    public static function getAll()
    {
        $reflector = new ReflectionClass(new Language());
        $constants = $reflector->getConstants();

        $values = [];
        foreach ($constants as $constant => $value) {
            $values[] = $value;
        }

        return $values;
    }

    public static function getLanguageStr($language)
    {
        if ($language == null || $language == '' || $language === self::LANGUAGE_HU) {
            $result = "magyar";
        } else {
            $result = match ($language) {
                self::LANGUAGE_EN => "angol",
                self::LANGUAGE_DE => "német",
                self::LANGUAGE_IT => "olasz",
                self::LANGUAGE_RO => "román",
                self::LANGUAGE_SK => "szlovák",
                self::LANGUAGE_HR => "horvát",
                self::LANGUAGE_FR => "francia",
                self::LANGUAGE_ES => "spanyol",
                self::LANGUAGE_CZ => "cseh",
                self::LANGUAGE_PL => "lengyel",
                default => "ismeretlen",
            };
        }

        return $result;
    }

    public function getAvailableLanguages()
    {
        return self::$availableLanguages;
    }
}
