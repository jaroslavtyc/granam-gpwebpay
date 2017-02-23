<?php
namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

class SrCodes extends StrictObject implements Codes
{
    private static $srCodes = [
        LanguageCodes::CS => [
            0 => '',
            1 => 'ORDERNUMBER',
            2 => 'MERCHANTNUMBER',
            6 => 'AMOUNT',
            7 => 'CURRENCY',
            8 => 'DEPOSITFLAG',
            10 => 'MERORDERNUM',
            11 => 'CREDITNUMBER',
            12 => 'OPERATION',
            18 => 'BATCH',
            22 => 'ORDER',
            24 => 'URL',
            25 => 'MD',
            26 => 'DESC',
            34 => 'DIGEST',
            1001 => 'Zamítnuto v autorizačním centru, katra je blokována',
            1002 => 'Zamítnuto v autorizačním centru, autorizace zamítnuta',
            1003 => 'Zamítnuto v autorizačním centru, problém karty',
            1004 => 'Zamítnuto v autorizačním centru, technický problém',
            1005 => 'Zamítnuto v autorizačním centru, Problém účtu',
            3000 => 'Neověřeno v 3D. Vydavatel karty není zapojen do 3D nebo karta nebyla aktivována.',
            3001 => 'Držitel karty ověřen.',
            3002 => 'Neověřeno v 3D. Vydavatel karty nebo karta není zapojena do 3D',
            3004 => 'Neověřeno v 3D. Vydavatel karty není zapojen do 3D nebo karta nebyla aktivována',
            3005 => 'Zamítnuto v 3D. Technický problém při ověření držitele karty',
            3006 => 'Zamítnuto v 3D. Technický problém při ověření držitele karty',
            3007 => 'Zamítnuto v 3D. Technický problém v systému zúčtující banky. Kontaktujte obchodníka',
            3008 => 'Zamítnuto v 3D. Použit nepodporavný karetní produkt',
        ],
        LanguageCodes::EN => [
            0 => '',
            1 => 'ORDERNUMBER',
            2 => 'MERCHANTNUMBER',
            6 => 'AMOUNT',
            7 => 'CURRENCY',
            8 => 'DEPOSITFLAG',
            10 => 'MERORDERNUM',
            11 => 'CREDITNUMBER',
            12 => 'OPERATION',
            18 => 'BATCH',
            22 => 'ORDER',
            24 => 'URL',
            25 => 'MD',
            26 => 'DESC',
            34 => 'DIGEST',
            1001 => 'Declined in AC, Card is blocked',
            1002 => 'Declined in AC, Declined',
            1003 => 'Declined in AC, Card problem',
            1004 => 'Declined in AC, Technical problem in authorization process',
            1005 => 'Declined in AC, Account problem',
            3000 => 'Not Authenticated in 3D. Cardholder not authenticated in 3D.',
            3001 => 'Authenticated',
            3002 => 'Not Authenticated in 3D. Issuer or Cardholder not participating in 3D.',
            3004 => 'Not Authenticated in 3D. Issuer not participating or Cardholder not enrolled.',
            3005 => 'Declined in 3D. Technical problem during Cardholder authentication.',
            3006 => 'Declined in 3D. Technical problem during Cardholder authentication.',
            3007 => 'Declined in 3D. Acquirer technical problem. Contact the merchant.',
            3008 => 'Declined in 3D. Unsupported card product.',
        ],
    ];

    /**
     * @return array|string[][][]
     */
    public static function getSrCodes()
    {
        return self::$srCodes;
    }

    const LANGUAGE_CS = LanguageCodes::CS;
    const LANGUAGE_EN = LanguageCodes::EN;

    /**
     * @param int $srCode
     * @param string $languageCode
     * @return string
     */
    public static function getLocalizedDetailMessage(int $srCode, string $languageCode = self::LANGUAGE_EN)
    {
        $languageCode = strtolower($languageCode);
        if (array_key_exists($languageCode, self::$srCodes) && array_key_exists($srCode, self::$srCodes[$languageCode])) {
            return self::$srCodes[$languageCode][$srCode];
        }
        if ($languageCode !== self::LANGUAGE_EN && array_key_exists($srCode, self::$srCodes[self::LANGUAGE_EN])) {
            trigger_error(
                "Unsupported language for error detail code requested: '{$languageCode}', " . self::LANGUAGE_EN . ' used instead',
                E_USER_NOTICE
            );

            return self::$srCodes[self::LANGUAGE_EN][$srCode]; // fallback
        }
        trigger_error("Unknown SR error code: '{$srCode}', no message about detail used", E_USER_WARNING);

        return '';
    }
}