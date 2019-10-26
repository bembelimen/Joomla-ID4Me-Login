<?php

namespace Id4me\RP;

use Id4me\RP\Model\Client;
use Id4me\RP\Model\OpenIdConfig;

/**
 * Class responsible of handling registration process
 */
class Registration
{
    /**
     * @var HttpClient;
     */
    private $httpClient;

    /**
     * Register specific client data using given identity authority
     *
     * @param OpenIdConfig $openIdConfig OpenId Config Instance containing data fetched from authority
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
        return $this->registerClient($openIdConfig, $identifier, $redirectUrl, $application_type, $requestHeaders);
    }

    /**
     * Registration constructor.
     *
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        $this->setHttpClient($client);
    }

    /**
     * Set current http client to another value
     *
     * @param HttpClient $httpClient
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Retrieves given client enriched with registration data
     *
     * @param OpenIdConfig $openIdConfig OpenId Config Instance containing data fetched from authority
     * @param string $identifier identifier of relying part request open id data (might be a domain or user name)
     * @param string $redirectUrl url of relying part requesting to be redirected to after successful user
     * authentication
     * @param string $application_type application type triggering registration process by authority
     * @param array $requestHeaders Headers that need to be set for the request in the http client
     *
     * @return Client
     */
    private function registerClient(
        OpenIdConfig $openIdConfig,
        string $identifier,
        string $redirectUrl,
        string $application_type = 'web',
        array $requestHeaders = []
    ) {
        $issuer               = $openIdConfig->getIssuer();
        $registrationEndPoint = $openIdConfig->getRegistrationEndpoint();

        $registrationRequest = [
            'client_name'      => $identifier,
            'application_type' => $application_type,
            'redirect_uris'    => [
                $redirectUrl
            ]
        ];
        
        if ($openIdConfig->getUserInfoSigningAlgValuesSupported() !== null
            && in_array('RS256', $openIdConfig->getUserInfoSigningAlgValuesSupported())) {
            $registrationRequest['userinfo_signed_response_alg'] = 'RS256';
        }

        $headers = array_merge(
            $requestHeaders,
            [
                'Content-Type' => 'application/json'
            ]
        );
        $result = $this->httpClient->post(
            $registrationEndPoint,
            json_encode($registrationRequest),
            $headers
        );

        $registrationData = json_decode($result, true);

        return $this->createClient($registrationData, $issuer, $redirectUrl);
    }

    /**
     * Retrieves properties identified by given property
     *
     * @param array $properties
     * @param string $property
     *
     * @return mixed|null
     */
    private function fetchClientProperty(array $properties, string $property)
    {
        return (isset($properties[$property])) ? $properties[$property] : null;
    }

    /**
     * Creates an instance of openId Client with given data
     *
     * @param array $registrationData
     * @param string $issuer
     * @param string $redirectUrl
     *
     * @return Client
     */
    private function createClient(array $registrationData, string $issuer, string $redirectUrl): Client
    {
        $client = new Client($issuer);

        $client->setClientName($this->fetchClientProperty($registrationData, 'client_name'));
        $client->setClientId($this->fetchClientProperty($registrationData, 'client_id'));
        $client->setClientSecret($this->fetchClientProperty($registrationData, 'client_secret'));
        $client->setClientExpirationTime(
            (int)$this->fetchClientProperty($registrationData, 'client_secret_expires_at')
        );
        $client->setActiveRedirectUri($redirectUrl);
        $client->setRedirectUris($this->fetchClientProperty($registrationData, 'redirect_uris'));
        $client->setUserInfoSignedResponseAlg(
            $this->fetchClientProperty($registrationData, 'userinfo_signed_response_alg')
        );

        return $client;
    }
}
