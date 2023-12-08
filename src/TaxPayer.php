<?php

namespace SzamlaAgent;

class TaxPayer
{
    const TAXPAYER_NON_EU_ENTERPRISE = 7;
    const TAXPAYER_EU_ENTERPRISE = 6;

    /**
     * Társas vállalkozás (Bt., Kft., zRt.)
     *
     * @deprecated 2.9.5 Ne használd, helyette használd ezt: TaxPayer::TAXPAYER_HAS_TAXNUMBER.
     */
    const TAXPAYER_JOINT_VENTURE = 5;

    /**
     * @deprecated 2.9.5 Ne használd, helyette használd ezt: TaxPayer::TAXPAYER_HAS_TAXNUMBER.
     */
    const TAXPAYER_INDIVIDUAL_BUSINESS = 4;

    /**
     * @deprecated 2.9.5 Ne használd, helyette használd ezt: TaxPayer::TAXPAYER_HAS_TAXNUMBER.
     */
    const TAXPAYER_PRIVATE_INDIVIDUAL_WITH_TAXNUMBER = 3;

    /**
     * @deprecated 2.9.5 Ne használd, helyette használd ezt: TaxPayer::TAXPAYER_HAS_TAXNUMBER.
     */
    const TAXPAYER_OTHER_ORGANIZATION_WITH_TAXNUMBER = 2;

    const TAXPAYER_HAS_TAXNUMBER = 1;
    const TAXPAYER_WE_DONT_KNOW = 0;
    const TAXPAYER_NO_TAXNUMBER = -1;

    /**
     * @deprecated 2.9.5 Ne használd, helyette használd ezt: TaxPayer::TAXPAYER_NO_TAXNUMBER.
     */
    const TAXPAYER_PRIVATE_INDIVIDUAL = -2;

    /**
     * @deprecated 2.9.5 Ne használd, helyette használd ezt: TaxPayer::TAXPAYER_NO_TAXNUMBER.
     */
    const TAXPAYER_OTHER_ORGANIZATION_WITHOUT_TAXNUMBER = -3;

    protected $taxPayerId;
    protected $taxPayerType;
    protected $requiredFields = ['taxPayerId'];

    public function __construct($taxpayerId = '', $taxPayerType = self::TAXPAYER_WE_DONT_KNOW)
    {
        $this->setTaxPayerId($taxpayerId);
        $this->setTaxPayerType($taxPayerType);
    }

    protected function getRequiredFields()
    {
        return $this->requiredFields;
    }

    protected function setRequiredFields(array $requiredFields): static
    {
        $this->requiredFields = $requiredFields;

        return $this;
    }

    public function getDefault()
    {
        return self::TAXPAYER_WE_DONT_KNOW;
    }

    protected function checkField($field, $value)
    {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'taxPayerType':
                    SzamlaAgentUtil::checkIntField($field, $value, $required, __CLASS__);
                    break;
                case 'taxPayerId':
                    SzamlaAgentUtil::checkStrFieldWithRegExp($field, $value, false, __CLASS__, '/[0-9]{8}/');
                    break;
            }
        }

        return $value;
    }

    protected function checkFields()
    {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            $this->checkField($field, $value);
        }
    }

    public function buildXmlData(SzamlaAgentRequest $request)
    {
        $this->checkFields();

        $data = [];
        $data["beallitasok"] = $request->getAgent()->getSetting()->buildXmlData($request);
        $data["torzsszam"] = $this->getTaxPayerId();

        return $data;
    }

    public function getTaxPayerId()
    {
        return $this->taxPayerId;
    }

    public function setTaxPayerId($taxPayerId): static
    {
        $this->taxPayerId = substr($taxPayerId, 0, 8);

        return $this;
    }

    public function getTaxPayerType()
    {
        return $this->taxPayerType;
    }

    /**
     *  7: TaxPayer::TAXPAYER_NON_EU_ENTERPRISE
     *  6: TaxPayer::TAXPAYER_EU_ENTERPRISE
     *  1: TaxPayer::TAXPAYER_HAS_TAXNUMBER
     *  0: TaxPayer::TAXPAYER_WE_DONT_KNOW
     * -1: TaxPayer::TAXPAYER_NO_TAXNUMBER
     *
     * @see https://tudastar.szamlazz.hu/gyik/vevo-adoszama-szamlan
     *
     * @param int $taxPayerType
     */
    public function setTaxPayerType($taxPayerType): static
    {
        $this->taxPayerType = $taxPayerType;

        return $this;
    }
}
