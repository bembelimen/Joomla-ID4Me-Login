<?php

namespace Id4me\RP;

use Id4me\RP\Exception\InvalidOpenIdDomainException;
use Id4me\RP\Exception\OpenIdDnsRecordNotFoundException;
use Id4me\RP\Model\OpenIdDnsRecord;

/**
 * This class is responsible of fetching OpenId Config Data matching to a specific consumerId
 * f.eg a domain from a dedicated openid authority
 *
 * Fetching data will be done in three steps
 *
 * 1. Extracting DNS Txt Records matching to concerned consumerId
 * 2. Extracting OpenId DNS Records found in DNS Txt Record list
 * 3. Fetching OpenId Config Data from a dedicated OpenId serving authority
 *
 * Note that a corresponding Exception might be raised after execution of steps enumerated above
 * in case expected prerequisite or injected data are missing or are invalid
 *
 * @package Id4me\RP
 */
class Discovery
{
    const RECORD_IDENTITY = 'OID1';
    const OPEN_ID_PREFIX = '_openid.';

    /**
     * Retrieves a OpenId Dns Record matching consumerId if found
     *
     * @param string $identifier identifier of consumer request open id data (might be a domain or user name)
     *
     * @return OpenIdDnsRecord
     *
     * @throws InvalidOpenIdDomainException if given consumerId is an invalid domain
     * @throws OpenIdDnsRecordNotFoundException if no matching openId DNS record is found
     */
    public function getOpenIdDnsRecord($identifier)
    {
        return $this->createOpenIdDnsRecord(
            $this->getDnsTxtRecords($identifier)
        );
    }

    /**
     * Retrieves a list of dns txt records matching given consumerId
     *
     * @param string $identifier identifier of consumer request open id data (might be a domain or user name)
     *
     * @return array
     *
     * @throws InvalidOpenIdDomainException if given consumerId is an invalid domain
     */
    public function getDnsTxtRecords($identifier)
    {
        $dnsRecords = [];

        // If domain isn't valid, try to resolve the record for the parent domain
        do {
            $openIdDomain = $this->retrieveOpenIdDomain($identifier);
            $isOpenIdDomainValid = $this->isValidOpenIdDomain($openIdDomain);

            if (!$isOpenIdDomainValid) {
                $identifier = $this->extractDomainFromIdentifierSubdomain($identifier);
            }
        } while (($identifier !== false) && !$isOpenIdDomainValid);

        // Still no domain resolving the record
        if (!$isOpenIdDomainValid) {
            throw new InvalidOpenIdDomainException(
                sprintf('Invalid OpenId Domain %s provided', $openIdDomain)
            );
        }

        // One domain found: retrieve record
        foreach ($this->getDnsTxtRecord($openIdDomain) as $record) {
            array_push($dnsRecords, $record['txt']);
        }

        return $dnsRecords;
    }

    /**
     * Extract potential domain or subdomain if $identifier is a subdomain
     *
     * @param  string $identifier
     * @return string
     */
    public function extractDomainFromIdentifierSubdomain($identifier)
    {
        $parts = explode('.', $identifier);
        $filtered_parts = array_filter($parts);

        if (count($filtered_parts) > 2) {
            return implode('.', array_splice($filtered_parts, 1));
        } else {
            return false;
        }
    }

    /**
     * Retrieves matching OpenIdDnsRecord out of given dns txt record list if found
     *
     * @param array $dnsTxtRecords
     *
     * @return OpenIdDnsRecord
     *
     * @throws OpenIdDnsRecordNotFoundException if no openId txt record is found
     */
    private function createOpenIdDnsRecord(array $dnsTxtRecords = [])
    {
        $openIdDnsRecord = null;
        $openIdDnsRecord = new OpenIdDnsRecord($dnsTxtRecords);

        if (is_null($openIdDnsRecord)) {
            throw new OpenIdDnsRecordNotFoundException('no openId DNS Record found');
        }

        return $openIdDnsRecord;
    }

    /**
     * @param $identifier
     *
     * @return array
     */
    private function getDnsTxtRecord($identifier)
    {
        return dns_get_record($identifier, DNS_TXT);
    }

    /**
     * Retrieves openId domain composed by specific openId prefix and given consumer id
     *
     * @param string $identifier
     *
     * @return string
     */
    private function retrieveOpenIdDomain($identifier)
    {
        return sprintf('%s%s', self::OPEN_ID_PREFIX, $identifier);
    }

    /**
     * Checks if given domain is a valid openId domain with corresponding DNS TXT Record
     *
     * @param $openIdDomain
     *
     * @return bool
     */
    private function isValidOpenIdDomain($openIdDomain)
    {
        return !empty($openIdDomain) && dns_check_record($openIdDomain, 'TXT');
    }
}
