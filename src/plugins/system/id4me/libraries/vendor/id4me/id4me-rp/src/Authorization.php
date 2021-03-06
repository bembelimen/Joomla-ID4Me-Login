<?php
namespace Id4me\RP;

use Id4me\RP\Exception\InvalidAuthorityIssuerException;
use Id4me\RP\Exception\InvalidIDTokenException;
use Id4me\RP\Exception\InvalidUserInfoException;
use Id4me\RP\Helper\OpenIdConfigHelper;
use Id4me\RP\Model\AuthorizationTokens;
use Id4me\RP\Model\IdToken;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\Model\JWT;
use Id4me\RP\Model\Client;
use Id4me\RP\Model\UserInfo;
use Id4me\RP\Model\ClaimRequestList;

/**
 * Class responsible of handling entire Authorization Process toward OpenId Authority
 */
class Authorization
{

    /**
     *
     * @var HttpClient;
     */
    private $httpClient;
/**
     *
     * @var \Id4me\RP\Validation;
     */
    private $validation;
/**
     *
     * @return \Id4me\RP\Validation;
     */
    private function getValidation(): Validation
    {
        return $this->validation;
    }

    /**
     * Authorization constructor.
     *
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        $this->setHttpClient($client);
        $this->validation = new Validation();
    }

    /**
     * Creates an authorization url out of given parameters
     *
     * @param string $authorizationEndpoint
     *            authority authorization endpoint to fetch url from
     * @param string $clientId
     *            clientId registered by authority
     * @param string $identifier
     *            identifier of consumer request open id data (might be a domain or user name)
     * @param string $redirectUrl
     *            url of relying part requesting to be redirected to after successful user authentication
     * @param string $state
     *            parameter containing data to be used by stateful mechanisms within relying part after http redirection
     * @param string $prompt
     *            parameter containing OIDC prompt parameter (none, login etc.)
     * @param ClaimRequestList|NULL  $userinfoclaims
     *            list of claims to be delivered in user info
     * @param ClaimRequestList|NULL  $idtokenclaims
     *            list of claims to be delivered in id_token
     * @param array $scopes
     *            list of requested scopes (openid scope will be added automatically)
     *
     * @return string
     */
    public function getAuthorizationUrl(
        string $authorizationEndpoint,
        string $clientId,
        string $identifier,
        string $redirectUrl,
        string $state = null,
        string $prompt = null,
        ClaimRequestList $userinfoclaims = null,
        ClaimRequestList $idtokenclaims = null,
        array $scopes = []
    ) {
        $query = [
            'client_id' => $clientId,
            'login_hint' => $identifier,
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code'
        ];
        if (! in_array("openid", $scopes)) {
            $scopes += [
                "openid"
            ];
        }

        $query['scope'] = implode(' ', $scopes);
        if (! empty($state)) {
            $query['state'] = $state;
        }

        if (! empty($prompt)) {
            $query['prompt'] = $prompt;
        }
        if ($userinfoclaims !== null || $idtokenclaims != null) {
            $claimsreq = [];
            if ($userinfoclaims != null) {
                $claimsreq['userinfo'] = $userinfoclaims->toOIDCAuthorizationRequestArray();
            }
            if ($idtokenclaims!= null) {
                $claimsreq['id_token'] = $idtokenclaims->toOIDCAuthorizationRequestArray();
            }
            $query['claims'] = json_encode($claimsreq);
        }

        return sprintf('%s?%s', $authorizationEndpoint, http_build_query($query, null, '&', PHP_QUERY_RFC3986));
    }

    /**
     * Retrieve access from authority after access tokens' validation
     *
     * @param OpenIdConfig $openIdConfig
     *            OpenId Config Instance containing data fetched from authority
     * @param string $code
     *            value of one time token generated by authority
     * @param string $redirectUrl
     *            url of relying part requesting to be redirected to after successful user authentication
     * @param string $clientId
     *            clientId registered by authority
     * @param string $clientSecret
     *            clientSecret registered by authority
     * @param array $requestHeaders
     *            Headers that need to be set for the request in the http client
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
    ): array {

        // Get authority validation key(s)
        $jwksArray = $this->getJwksArray($openIdConfig->getJwksUri());
// Get authority response with authorization code
        $accessTokens = $this->getAccessTokens($openIdConfig, $code, $redirectUrl, $clientId, $clientSecret);
// Extract and validate ID token data
        if (! isset($accessTokens['id_token'])) {
            throw new InvalidIDTokenException('ID Token not found');
        }

        $idToken = new IdToken($accessTokens['id_token']);
        $this->getValidation()->validateIdToken($idToken, $openIdConfig, $jwksArray, $clientId);
        $ret = array_merge($accessTokens, [
                'identifier' => $idToken->getId4meIdentifier(),
                'iss' => $idToken->getIss(),
                'sub' => $idToken->getSub(),
                'id_token_decoded' => $idToken
            ]);
        return $ret;
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
     * @param string|NULL $redirectUrl
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

        $accessTokens = $this->authorize(
            $openIdConfig,
            $code,
            $redirectUrl ?? $client->getActiveRedirectUri(),
            $client->getClientId(),
            $client->getClientSecret()
        );
        return new AuthorizationTokens($accessTokens);
    }


    /**
     * Retrieve JWS keys from authority
     *
     * @param string $jwksUri
     *
     * @return array
     *
     * @throws InvalidIDTokenException
     */
    public function getJwksArray(string $jwksUri): array
    {
        $response = $this->httpClient->get($jwksUri);
        if (! $response) {
            throw new InvalidIDTokenException('Unable to retrieve authority JWS keys');
        }

        return json_decode($response, true);
    }

    /**
     * Retrieve access tokens from authority
     *
     * @param OpenIdConfig $openIdConfig
     * @param string $code
     * @param string $redirectUrl
     * @param string $clientId
     * @param string $clientSecret
     * @param array $requestHeaders
     *            Headers that need to be set for the request in the http client
     *
     * @return array
     *
     * @throws InvalidIDTokenException
     */
    public function getAccessTokens(
        OpenIdConfig $openIdConfig,
        string $code,
        string $redirectUrl,
        string $clientId,
        string $clientSecret,
        array $requestHeaders = []
    ): array {
        $headers = array_merge($requestHeaders, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret)
        ]);
        $response = $this->httpClient->post($openIdConfig->getTokenEndpoint(), http_build_query([
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUrl
        ]), $headers);
        if (! $response) {
            throw new InvalidIDTokenException('Unable to retrieve access tokens');
        }

        return json_decode($response, true);
    }

    /**
     * Retrieve User Info from claims provider, including distributed claims if neededs
     *
     * @param OpenIdConfig $openIdConfig
     * @param Client $client
     * @param AuthorizationTokens $authzTokens
     * @param int $distributedClaimsDepth
     *            Indicates how many nested UserInfo sources will be queried
     * @param array $requestHeaders
     *            Headers that need to be set for the request in the http client
     *
     * @return UserInfo
     *
     * @throws InvalidUserInfoException
     */
    public function getUserInfo(
        OpenIdConfig $openIdConfig,
        Client $client,
        AuthorizationTokens $authzTokens,
        int $distributedClaimsDepth = 3,
        array $requestHeaders = []
    ): UserInfo {
        return new UserInfo(
            $this->makeUserInfoRequest(
                $openIdConfig->getUserInfoEndpoint(),
                $client,
                $openIdConfig,
                $authzTokens->getAccessToken(),
                $distributedClaimsDepth,
                $requestHeaders
            )
        );
    }

    /**
     * Retrieve User Info from claims provider, including distributed claims if neededs
     *
     * @param string $endPoint
     * @param Client $client
     * @param OpenIdConfig|NULL $openIdConfig
     * @param string $accessToken
     * @param int $distributedClaimsDepth
     * @param array $requestHeaders
     *            Headers that need to be set for the request in the http client
     *
     * @return array
     *
     * @throws InvalidUserInfoException
     */
    private function makeUserInfoRequest(
        string $endPoint,
        Client $client,
        OpenIdConfig $openIdConfig = null,
        string $accessToken,
        int $distributedClaimsDepth = 3,
        array $requestHeaders = []
    ): array {
        $headers = array_merge($requestHeaders, [
            'Authorization' => 'Bearer ' . $accessToken
        ]);
        if ($client->getUserInfoSignedResponseAlg() !== null) {
            $headers['Accept'] = 'application/jwt';
        } else {
            $headers['Accept'] = 'application/json';
        }

        $response = $this->httpClient->get($endPoint, array_merge($headers, $requestHeaders));
        if (! $response) {
            throw new InvalidUserInfoException('Unable to retrieve user info');
        }

        $userInfo = null;
        if ($client->getUserInfoSignedResponseAlg() !== null) {
            $userInfoJwt = new JWT($response);
        // if there is no config known upfront, iss shall be used to get one
            if ($openIdConfig === null) {
                $iss = $userInfoJwt->getIss();
                $openIdConfig = OpenIdConfigHelper::instance()->createFromIssuerIdentifier($iss, $this->httpClient);
            }

            // Get authority validation key(s)
            $jwksArray = $this->getJwksArray($openIdConfig->getJwksUri());
            $this->getValidation()->validateUserInfo($userInfoJwt, $openIdConfig, $jwksArray, $client->getClientId());
            $userInfo = $userInfoJwt->getDecodedBody();
        } else {
            $userInfo = json_decode($response);
        }

        if ($distributedClaimsDepth > 0) {
            $addedclaims = $this->processDistributedClaims(
                $client,
                $distributedClaimsDepth,
                $userInfo,
                $requestHeaders
            );
            $userInfo = array_diff_key($userInfo, array_flip([
                '_claim_sources',
                '_claim_names'
            ]));
            $userInfo = array_merge($userInfo, $addedclaims);
        }

        return $userInfo;
    }

    /**
     * Processes distributed and aggregated claims
     *
     * @param Client $client
     * @param int $distributedClaimsDepth
     *            counter to finish the recoursion
     * @param array $userInfo
     * @param array $requestHeaders
     * @return array
     */
    private function processDistributedClaims(
        Client $client,
        int $distributedClaimsDepth,
        array $userInfo,
        array $requestHeaders = []
    ): array {
        $addedclaims = [];
        if (array_key_exists('_claim_sources', $userInfo) && array_key_exists('_claim_names', $userInfo)) {
            foreach ($userInfo['_claim_sources'] as $claimsourcename => $claimsourcedef) {
                if (is_array($claimsourcedef)) {
                    // distributed claims
                    if (array_key_exists('endpoint', $claimsourcedef)
                        && array_key_exists('access_token', $claimsourcedef)) {
                        $remoteclaims = $this->makeUserInfoRequest(
                            $claimsourcedef['endpoint'],
                            $client,
                            null, // NULL as it will be different issuer
                            $claimsourcedef['access_token'],
                            $distributedClaimsDepth - 1,
                            $requestHeaders
                        );
                        foreach ($remoteclaims as $remoteclaimname => $remoteclaimvalue) {
                            if (array_key_exists($remoteclaimname, $userInfo['_claim_names'])
                                && $userInfo['_claim_names'][$remoteclaimname] === $claimsourcename) {
                                $addedclaims[$remoteclaimname] = $remoteclaimvalue;
                            }
                        }
                    }
                } else {
                    trigger_error('Aggregated claims not implemented');
                }
            }
        }
        return $addedclaims;
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
     * Set current validation handler to another value
     *
     * @param Validation $validation
     */
    public function setValidation(Validation $validation)
    {
        $this->validation = $validation;
    }
}
