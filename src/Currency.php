<?php

namespace SzamlaAgent;

class Currency
{
    const CURRENCY_FT  = 'Ft';
    const CURRENCY_HUF = 'HUF';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_CHF = 'CHF';
    const CURRENCY_USD = 'USD';
    const CURRENCY_AED = 'AED';
    const CURRENCY_AUD = 'AUD';
    const CURRENCY_BGN = 'BGN';
    const CURRENCY_BRL = 'BRL';
    const CURRENCY_CAD = 'CAD';
    const CURRENCY_CNY = 'CNY';
    const CURRENCY_CZK = 'CZK';
    const CURRENCY_DKK = 'DKK';
    const CURRENCY_EEK = 'EEK';
    const CURRENCY_GBP = 'GBP';
    const CURRENCY_HKD = 'HKD';
    const CURRENCY_HRK = 'HRK';
    const CURRENCY_IDR = 'IDR';
    const CURRENCY_ILS = 'ILS';
    const CURRENCY_INR = 'INR';
    const CURRENCY_ISK = 'ISK';
    const CURRENCY_JPY = 'JPY';
    const CURRENCY_KRW = 'KRW';
    const CURRENCY_LTL = 'LTL';
    const CURRENCY_LVL = 'LVL';
    const CURRENCY_MXN = 'MXN';
    const CURRENCY_MYR = 'MYR';
    const CURRENCY_NOK = 'NOK';
    const CURRENCY_NZD = 'NZD';
    const CURRENCY_PHP = 'PHP';
    const CURRENCY_PLN = 'PLN';
    const CURRENCY_RON = 'RON';
    const CURRENCY_RSD = 'RSD';
    const CURRENCY_RUB = 'RUB';
    const CURRENCY_SEK = 'SEK';
    const CURRENCY_SGD = 'SGD';
    const CURRENCY_THB = 'THB';
    const CURRENCY_TRY = 'TRY';
    const CURRENCY_UAH = 'UAH';
    const CURRENCY_VND = 'VND';
    const CURRENCY_ZAR = 'ZAR';

    public static function getDefault()
    {
        return self::CURRENCY_FT;
    }

    public static function getCurrencyStr($currency)
    {
        if ($currency == null || $currency == '' || $currency === "Ft" || $currency == "HUF") {
            $result = "forint";
        } else {
            $result = match ($currency) {
                self::CURRENCY_EUR => "euró",
                self::CURRENCY_USD => "amerikai dollár",
                self::CURRENCY_AUD => "ausztrál dollár",
                self::CURRENCY_AED => "Arab Emírségek dirham",
                self::CURRENCY_BRL => "brazil real",
                self::CURRENCY_CAD => "kanadai dollár",
                self::CURRENCY_CHF => "svájci frank",
                self::CURRENCY_CNY => "kínai jüan",
                self::CURRENCY_CZK => "cseh korona",
                self::CURRENCY_DKK => "dán korona",
                self::CURRENCY_EEK => "észt korona",
                self::CURRENCY_GBP => "angol font",
                self::CURRENCY_HKD => "hongkongi dollár",
                self::CURRENCY_HRK => "horvát kúna",
                self::CURRENCY_ISK => "izlandi korona",
                self::CURRENCY_JPY => "japán jen",
                self::CURRENCY_LTL => "litván litas",
                self::CURRENCY_LVL => "lett lat",
                self::CURRENCY_MXN => "mexikói peso",
                self::CURRENCY_NOK => "norvég koron",
                self::CURRENCY_NZD => "új-zélandi dollár",
                self::CURRENCY_PLN => "lengyel zloty",
                self::CURRENCY_RON => "új román lej",
                self::CURRENCY_RUB => "orosz rubel",
                self::CURRENCY_SEK => "svéd koron",
                self::CURRENCY_UAH => "ukrán hryvna",
                self::CURRENCY_BGN => "bolgár leva",
                self::CURRENCY_RSD => "szerb dínár",
                self::CURRENCY_ILS => "izraeli sékel",
                self::CURRENCY_IDR => "indonéz rúpia",
                self::CURRENCY_INR => "indiai rúpia",
                self::CURRENCY_TRY => "török líra",
                self::CURRENCY_VND => "vietnámi dong",
                self::CURRENCY_SGD => "szingapúri dollár",
                self::CURRENCY_THB => "thai bát",
                self::CURRENCY_KRW => "dél-koreai won",
                self::CURRENCY_MYR => "maláj ringgit",
                self::CURRENCY_PHP => "fülöp-szigeteki peso",
                self::CURRENCY_ZAR => "dél-afrikai rand",
                default => "ismeretlen",
            };
        }

        return $result;
    }
}
