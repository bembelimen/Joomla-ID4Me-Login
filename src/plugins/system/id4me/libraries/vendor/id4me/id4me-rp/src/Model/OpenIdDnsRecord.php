<?php

namespace Id4me\RP\Model;

/**
 * Container class responsible of encapsulating OpenId DNS Record Data provided by openId DNS Discovery Process
 *
 * @package Id4me\RP\Model
 */
class OpenIdDnsRecord
{
    const RECORD_DATA_V = 'v';
    const RECORD_DATA_ISS = 'iss';
    const RECORD_DATA_CLP = 'clp';
    const OID_1 = "OID1";

    /**
     * domainTextRecord
     *
     * @var array
     */
    protected $dnsTxtRecords = [];

    /**
     * variant
     *
     * @var string
     */

    protected $variant = '';

    /**
     * identityAgent
     *
     * @var string
     */
    protected $identityAgent = '';

    /**
     * identityAuthority
     *
     * @var string
     */
    protected $identityAuthority = '';

    /**
     * Creates an instance of OpenIdDnsRecord
     *
     * @param array $openIdTxtRecords
     */
    public function __construct(array $openIdTxtRecords = [])
    {
        $this->init($openIdTxtRecords);
    }

    /**
     * Retrieves list of current txt records
     *
     * @return array
     */
    public function getDnsTxtRecords()
    {
        return $this->dnsTxtRecords;
    }

    /**
     * Retrieves current txt record variant
     *
     * @return string
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * Retrieves current identoty agent
     *
     * @return string
     */
    public function getIdentityAgent()
    {
        return $this->identityAgent;
    }

    /**
     * Retrieves IdentiyAuthority
     *
     * @return string
     */
    public function getIdentityAuthority()
    {
        return $this->identityAuthority;
    }

    /**
     * Initializes DNS Records Properties
     *
     * @param array $openIdTxtRecords
     */
    private function init(array $openIdTxtRecords = [])
    {
        foreach ($openIdTxtRecords as $value) {
            if ($this->isValidOpenIdRecord($value) !== false) {
                $dnsRecordArray = $this->createDnsRecordArray($value);

                $this->dnsTxtRecords     = $value;
                $this->variant           = $this->fetchDnsRecordProperty($dnsRecordArray, self::RECORD_DATA_V);
                $this->identityAuthority = $this->fetchDnsRecordProperty($dnsRecordArray, self::RECORD_DATA_ISS);
                $this->identityAgent     = $this->fetchDnsRecordProperty($dnsRecordArray, self::RECORD_DATA_CLP);

                break;
            }
        }
    }

    private function isValidOpenIdRecord($record)
    {
        return strpos($record, 'v=' . self::OID_1);
    }

    /**
     * Retrieves Dns Record Property if found
     *
     * @param array  $openIdTxtRecords
     * @param string $property
     *
     * @return mixed|null
     */
    private function fetchDnsRecordProperty(array $openIdTxtRecords, $property)
    {
        return (isset($openIdTxtRecords[$property])) ? $openIdTxtRecords[$property] : null;
    }

    /**
     * @param  string $value
     * @return array
     */
    private function createDnsRecordArray($value)
    {
        $recordValues = explode(";", $value);
        $result = array();

        foreach ($recordValues as $value) {
            $e = explode("=", $value);
            $result[$e[0]] = $e[1];
        }

        return $result;
    }
}
