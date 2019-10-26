<?php

namespace Id4me\RP;

use Id4me\RP\Exception\InvalidAuthorityIssuerException;
use Id4me\RP\Exception\InvalidIDTokenException;
use Id4me\RP\Exception\InvalidOpenIdDomainException;
use Id4me\RP\Exception\OpenIdDnsRecordNotFoundException;
use Id4me\RP\Helper\OpenIdConfigHelper;
use Id4me\RP\Model\AuthorizationTokens;
use Id4me\RP\Model\Client;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\Model\UserInfo;
use Id4me\RP\Model\ClaimRequestList;

/**
 * This class is a Service Facade encapsulating following main use cases scenarios supported by Id4Me Library:
 *
 * - Discovery
 * - Registration
 * - Authorization
 */
class Service
{
    /**
     * @var Discovery
     */
    private $discovery;

    /**
     * @var Registration
     */
    private $registration;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var OpenIdConfigHelper
     */
    private $openConfigHelper;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Creates an instance of Id4Me Service
     *
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        $this->discovery        = new Discovery();
        $this->registration     = new Registration($client);
        $this->authorization    = new Authorization($client);
        $this->openConfigHelper = OpenIdConfigHelper::instance();

        $this->httpClient = $client;
    }

    /**
     * Triggers discovery process with given identifier and retrieves matching authority name if found
     *
     * @param string $identifier identifier of relying part request open id data (might be a domain or user name)
     *
     * @throws InvalidOpenIdDomainException if given identifier does not match any valid openId domain
     * @throws OpenIdDnsRecordNotFoundException if no matching openId DNS record is found
     *
     * @return string
     */
    public function discover(string $identifier)
    {
        return $this->discovery->getOpenIdDnsRecord($identifier)->getIdentityAuthority();
    }

    /**
     * Registers and retrieves openId client data in accordance to given client and authority identifiers
     *
     * @param OpenIdConfig $openIdConfig
     * @param string $identifier identifier of relying part request open id data (might be a domain or user name)
     * @param string $redirectUrl url of relying part requesting to be redirected to after successful user
     * authentication
     * @param array $requestHeaders Headers that need to be set for the request in the http client
     * @param string $application_type application type triggering registration process by authority
     *
     * @return Client
     */
    public function register(
        OpenIdConfig $openIdConfig,
        string $identifier,
        string $redirectUrl,
        string $application_type = 'web',
        array $requestHeaders = []
    ) {
        return $this->registration->register(
            $openIdConfig,
            $identifier,
            $redirectUrl,
            $application_type,
            $requestHeaders
        );
    }

    /**
     * Creates and retrieves an authorization url out of given authorization parameters
     *
     * @param OpenIdConfig $openIdConfig OpenId Config Instance containing data fetched from authority
     * @param string $clientId clientId registered by authority
     * @param string $identifier identifier of consumer request open id data (might be a domain or user name)
     * @param string $redirectUrl url of relying part requesting to be redirected to after successful user
     * authentication
     * @param string $state parameter containing data to be used by stateful mechanisms within relying part after http
     * redirection
     * @param string $prompt                parameter containing OIDC prompt parameter (none, login etc.)
     * @param ClaimRequestList|NULL  $userinfoclaims        list of claims to be delivered in user info
     * @param ClaimRequestList|NULL  $idtokenclaims        list of claims to be delivered in id_token
     * @param array  $scopes                list of requested scopes (openid scope will be added automatically)
     *
     * @return string
     */
    public function getAuthorizationUrl(
        OpenIdConfig $openIdConfig,
        string $clientId,
        string $identifier,
        string $redirectUrl,
        string $state = null,
        string $prompt = null,
        ClaimRequestList $userinfoclaims = null,
        ClaimRequestList $idtokenclaims = null,
        array $scopes = []
    ) {
        return $this->authorization->getAuthorizationUrl(
            $openIdConfig->getAuthorizationEndpoint(),
            $clientId,
            $identifier,
            $redirectUrl,
            $state,
            $prompt,
            $userinfoclaims,
            $idtokenclaims,
            $scopes
        );
    }

    /**
     * Retrieves access token data fetched from authority
     *
     * @param OpenIdConfig $openIdConfig OpenId Config Instance containing data fetched from authority
     * @param string $code value of one time token generated by authority
     * @param string $redirectUrl url of relying part requesting to be redirected to after successful user
     * authentication
     * @param string $clientId clientId registered by authority
     * @param string $clientSecret clientSecret registered by authority
     *
     * @return array
     *
     * @throws InvalidAuthorityIssuerException if iss and issuer values are not equal
     * @throws InvalidIDTokenException if provided id token value does not pass any of 13 validation steps
     *         described in ID Token Validation specifications of ID4Me Documentation
     */
    public function authorize(
        OpenIdConfig $openIdConfig,
        string $code,
        string $redirectUrl,
        string $clientId,
        string $clientSecret
    ) {
        return $this->authorization->authorize(
            $openIdConfig,
            $code,
            $redirectUrl,
            $clientId,
            $clientSecret
        );
    }

    /**
     * Retrieve access from authority after access tokens' validation
     *
     * @param OpenIdConfig $openIdConfig
     *            OpenId Config Instance containing data fetched from authority
     * @param string $code
     *            value of one time token generated by authority
     * @param Client $client
     *            client object with registration data
     * @param string $redirectUrl|NULL
     *            url of relying part requesting to be redirected to after successful user authentication
     *            if not provided it's taken from the $client
     *
     * @return AuthorizationTokens
     *
     * @throws InvalidAuthorityIssuerException if iss and issuer values are not equal
     * @throws InvalidIDTokenException if provided id token value does not pass any of 13 validation steps
     *         described in ID Token Validation specifications of ID4Me Documentation
     */
    public function getAuthorizationTokens(
        OpenIdConfig $openIdConfig,
        string $code,
        Client $client,
        string $redirectUrl = null
    ): AuthorizationTokens {
        return $this->authorization->getAuthorizationTokens($openIdConfig, $code, $client, $redirectUrl);
    }

    /**
     * Retriever User Info given the access token
     *
     * @param OpenIdConfig          $openIdConfig
     * @param Client                $client
     * @param AuthorizationTokens   $authzTokens
     * @param int                   $distributedClaimsDepth
     *
     * @return UserInfo
     */
    public function getUserInfo(
        OpenIdConfig $openIdConfig,
        Client $client,
        AuthorizationTokens $authzTokens,
        int $distributedClaimsDepth = 3
    ): UserInfo {
        return $this->authorization->getUserInfo(
            $openIdConfig,
            $client,
            $authzTokens,
            $distributedClaimsDepth
        );
    }

    /**
     * Retrieves OpenId Config Data fetched from given identity authority
     *
     * @param string $identityAuthority identity authority to fetch openId Config from
     *
     * @throws InvalidAuthorityIssuerException
     * @return OpenIdConfig
     *
     * @throws InvalidAuthorityIssuerException
     */
    public function getOpenIdConfig(string $identityAuthority)
    {
        return $this->openConfigHelper->createFromAuthority($identityAuthority, $this->httpClient);
    }

    /**
     * Retrieves an OpenId Config Instance generated from given json data
     *
     * @param string $openIdJsonData
     *
     * @return OpenIdConfig
     */
    public function createOpenIdConfigFromJson(string $openIdJsonData)
    {
        return $this->openConfigHelper->createFromJson($openIdJsonData);
    }

    /**
     * Sets current Discovery instance to a new value
     *
     * @param Discovery $discovery
     */
    public function setDiscovery(Discovery $discovery)
    {
        $this->discovery = $discovery;
    }

    /**
     * Sets current Registration instance to a new value
     *
     * @param Registration $registration
     */
    public function setRegistration(Registration $registration)
    {
        $this->registration = $registration;
    }

    /**
     * Sets current Authorization instance to a new value
     *
     * @param Authorization $authorization
     */
    public function setAuthorization(Authorization $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Sets current OpenIdConfigHelper instance to a new value
     *
     * @param OpenIdConfigHelper $openConfigHelper
     */
    public function setOpenConfigHelper(OpenIdConfigHelper $openConfigHelper)
    {
        $this->openConfigHelper = $openConfigHelper;
    }
}
