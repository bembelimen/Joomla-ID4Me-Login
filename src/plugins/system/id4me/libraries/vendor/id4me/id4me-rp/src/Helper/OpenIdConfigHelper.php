<?php

namespace Id4me\RP\Helper;

use Id4me\RP\Exception\InvalidAuthorityIssuerException;
use Id4me\RP\HttpClient;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\Validation;

/**
 * This class is responsible of handling OpenId Config Data encapsulated in suitable OpenIdConfig container class.
 *
 * Following use case will be covered:
 *
 * - Fetching OpenId Config Data from authority per http request
 * - Retrieving OpenId Config Data in in suitable OpenIdConfig container class
 */
class OpenIdConfigHelper
{
    /**
     * @var OpenIdConfigHelper
     */
    private static $instance;

    /**
     * @var Validation
     */
    private $validation;

    /**
     * Creates and retrieves an instance of OpenIdConfigHelper
     *
     * @return OpenIdConfigHelper
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $issuerIdentifierURL
     *
     * @return string
     */
    private static function getOpenIdConfigurationUrlForIssuerIdentifierURL(string $issuerIdentifierURL)
    {
        return sprintf(
            '%s%s.well-known/openid-configuration',
            $issuerIdentifierURL,
            substr($issuerIdentifierURL, -1) === '/' ? '' : '/'
        );
    }


    /**
     * OpenIdConfigHelper constructor.
     */
    private function __construct()
    {
        $this->validation = new Validation();
    }

    /**
     * Creates an instance of OpenIdConfig using given identity authority (host + path as in iss
     * property of ID4me TXT record)
     *
     * Note that an http client will be used to fetch required data from authority via http request
     *
     * @param string $identityAuthority
     * @param HttpClient $httpClient
     * @param array $requestHeaders Headers that need to be set for the request in the http client
     *
     * @return OpenIdConfig
     *
     * @throws InvalidAuthorityIssuerException
     */
    public function createFromAuthority(string $identityAuthority, HttpClient $httpClient, array $requestHeaders = [])
    {
        return $this->createFromIssuerIdentifier(
            sprintf(
                'https://%s',
                $identityAuthority
            ),
            $httpClient
        );
    }


    /**
     * Creates an instance of OpenIdConfig using given full identity authority URL,
     * retrieving the value from .well-know OpenID Configuration
     *
     * Note that an http client will be used to fetch required data from authority via http request
     *
     * @param string $issuerIdentifierURL
     * @param HttpClient $httpClient
     * @param array $requestHeaders Headers that need to be set for the request in the http client
     *
     * @return OpenIdConfig
     *
     * @throws InvalidAuthorityIssuerException
     */
    public function createFromIssuerIdentifier(
        string $issuerIdentifierURL,
        HttpClient $httpClient,
        array $requestHeaders = []
    ) {
        if (empty($issuerIdentifierURL)) {
            throw new InvalidAuthorityIssuerException('no iss value given to retrieve OpenID configuration');
        }

        $wellKnownUrl  = OpenIdConfigHelper::getOpenIdConfigurationUrlForIssuerIdentifierURL($issuerIdentifierURL);
        $response = $httpClient->get(
            $wellKnownUrl,
            $requestHeaders
        );

        $openIdConfigData = preg_replace('# |\r|\n#', '', $response);
        $openIdConfig = $this->createFromJson($openIdConfigData);

        $this->validation->validateISS($issuerIdentifierURL, $openIdConfig->getIssuer(), true);

        return $openIdConfig;
    }

    /**
     * Creates an instance of OpenIdConfig using given openId Config Data array
     *
     * @param array $openIdConfigDataArray
     *
     * @return OpenIdConfig
     */
    public function createFromArray(array $openIdConfigDataArray)
    {
        return new OpenIdConfig($openIdConfigDataArray);
    }

    /**
     * Creates an instance of OpenIdConfig using given openId Config Data Json value
     *
     * @param string $openIdConfigDataJson
     *
     * @return OpenIdConfig
     */
    public function createFromJson(string $openIdConfigDataJson)
    {
        return $this->createFromArray(json_decode($openIdConfigDataJson, true));
    }
}
