<?php
namespace Id4me\RP\Model;

/**
 * Container class responsible of encapsulating OpenId Config Data provided by openId authority
 *
 * @package Id4me\RP\Model
 */
class OpenIdConfig
{
    /**
     * @var string
     */
    protected $issuer;

    /**
     * @var string
     */
    protected $jwks_uri;

    /**
     * @var string
     */
    protected $authorization_endpoint;

    /**
     * @var string
     */
    protected $token_endpoint;

    /**
     * @var string
     */
    protected $registration_endpoint;

    /**
     * @var string
     */
    protected $introspection_endpoint;

    /**
     * @var string
     */
    protected $revocation_endpoint;

    /**
     * @var string
     */
    protected $userinfo_endpoint;

    /**
     * @var string
     */
    protected $end_session_endpoint;

    /**
     * @var array
     */
    protected $scopes_supported;

    /**
     * @var array
     */
    protected $response_types_supported;

    /**
     * @var array
     */
    protected $response_modes_supported;

    /**
     * @var array
     */
    protected $grant_types_supported;

    /**
     * @var array
     */
    protected $code_challenge_methods_supported;

    /**
     * @var array
     */
    protected $token_endpoint_auth_methods_supported;

    /**
     * @var array
     */
    protected $token_endpoint_auth_signing_alg_values_supported;

    /**
     * @var array
     */
    protected $request_object_signing_alg_values_supported;

    /**
     * @var array
     */
    protected $ui_locales_supported;

    /**
     * @var boolean
     */
    protected $request_parameter_supported;

    /**
     * @var array
     */
    protected $id_token_signing_alg_values_supported;

    /**
     * @var array
     */
    protected $id_token_encryption_enc_values_supported;

    /**
     * @var array
     */
    protected $userinfo_signing_alg_values_supported;

    /**
     * @var array
     */
    protected $userinfo_encryption_alg_values_supported;

    /**
     * @var array
     */
    protected $display_values_supported;

    /**
     * @var array
     */
    protected $claim_types_supported;

    /**
     * @var array
     */
    protected $claims_supported;

    /**
     * @var boolean
     */
    protected $claims_parameter_supported;

    /**
     * @var boolean
     */
    protected $front_channel_logout_supported;

    /**
     * @var boolean
     */
    protected $back_channel_logout_supported;

    /**
     * @var boolean
     */
    protected $request_uri_parameter_supported;

    /**
     * @var boolean
     */
    protected $require_request_uri_registration;

    /**
     * @var int
     */
    protected $tls_client_certificate_bound_access_tokens;

    /**
     * @var int
     */
    protected $request_uri_quota;

    /**
     * @var array
     */
    protected $openIdConfigData;

    /**
     * OpenIdConfig constructor.
     *
     * @param array $openIdConfigData
     */
    public function __construct(array $openIdConfigData)
    {
        $this->init($openIdConfigData);
    }

    /**
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * @param string $issuer
     */
    public function setIssuer(string $issuer)
    {
        $this->issuer = $issuer;
    }

    /**
     * @return string
     */
    public function getJwksUri(): string
    {
        return $this->jwks_uri;
    }

    /**
     * @param string $jwksUri
     */
    public function setJwksUri(string $jwksUri)
    {
        $this->jwks_uri = $jwksUri;
    }

    /**
     * @return string
     */
    public function getAuthorizationEndpoint(): string
    {
        return $this->authorization_endpoint;
    }

    /**
     * @param string $authorizationEndpoint
     */
    public function setAuthorizationEndpoint(string $authorizationEndpoint)
    {
        $this->authorization_endpoint = $authorizationEndpoint;
    }

    /**
     * @return string
     */
    public function getTokenEndpoint(): string
    {
        return $this->token_endpoint;
    }

    /**
     * @param string $tokenEndpoint
     */
    public function setTokenEndpoint(string $tokenEndpoint)
    {
        $this->token_endpoint = $tokenEndpoint;
    }

    /**
     * @return string
     */
    public function getRegistrationEndpoint(): string
    {
        return $this->registration_endpoint;
    }

    /**
     * @param string $registrationEndpoint
     */
    public function setRegistrationEndpoint(string $registrationEndpoint)
    {
        $this->registration_endpoint = $registrationEndpoint;
    }

    /**
     * @return string
     */
    public function getIntrospectionEndpoint(): string
    {
        return $this->introspection_endpoint;
    }

    /**
     * @param string $introspectionEndpoint
     */
    public function setIntrospectionEndpoint(string $introspectionEndpoint)
    {
        $this->introspection_endpoint = $introspectionEndpoint;
    }

    /**
     * @return string
     */
    public function getRevocationEndpoint(): string
    {
        return $this->revocation_endpoint;
    }

    /**
     * @param string $revocationEndpoint
     */
    public function setRevocationEndpoint(string $revocationEndpoint)
    {
        $this->revocation_endpoint = $revocationEndpoint;
    }

    /**
     * @return string
     */
    public function getUserInfoEndpoint(): string
    {
        return $this->userinfo_endpoint;
    }

    /**
     * @param string $userInfoEndpoint
     */
    public function setUserInfoEndpoint(string $userInfoEndpoint)
    {
        $this->userinfo_endpoint = $userInfoEndpoint;
    }

    /**
     * @return string
     */
    public function getEndSessionEndpoint(): string
    {
        return $this->end_session_endpoint;
    }

    /**
     * @param string $endSessionEndpoint
     */
    public function setEndSessionEndpoint(string $endSessionEndpoint)
    {
        $this->end_session_endpoint = $endSessionEndpoint;
    }

    /**
     * @return array
     */
    public function getScopesSupported(): array
    {
        return $this->scopes_supported;
    }

    /**
     * @param array $scopesSupported
     */
    public function setScopesSupported(array $scopesSupported)
    {
        $this->scopes_supported = $scopesSupported;
    }

    /**
     * @return array
     */
    public function getResponseTypesSupported(): array
    {
        return $this->response_types_supported;
    }

    /**
     * @param array $responseTypesSupported
     */
    public function setResponseTypesSupported(array $responseTypesSupported)
    {
        $this->response_types_supported = $responseTypesSupported;
    }

    /**
     * @return array
     */
    public function getResponseModesSupported(): array
    {
        return $this->response_modes_supported;
    }

    /**
     * @param array $responseModesSupported
     */
    public function setResponseModesSupported(array $responseModesSupported)
    {
        $this->response_modes_supported = $responseModesSupported;
    }

    /**
     * @return array
     */
    public function getGrantTypesSupported(): array
    {
        return $this->grant_types_supported;
    }

    /**
     * @param array $grantTypesSupported
     */
    public function setGrantTypesSupported(array $grantTypesSupported)
    {
        $this->grant_types_supported = $grantTypesSupported;
    }

    /**
     * @return array
     */
    public function getCodeChallengeMethodsSupported(): array
    {
        return $this->code_challenge_methods_supported;
    }

    /**
     * @param array $codeChallengeMethodsSupported
     */
    public function setCodeChallengeMethodsSupported(array $codeChallengeMethodsSupported)
    {
        $this->code_challenge_methods_supported = $codeChallengeMethodsSupported;
    }

    /**
     * @return array
     */
    public function getTokenEndpointAuthMethodsSupported(): array
    {
        return $this->token_endpoint_auth_methods_supported;
    }

    /**
     * @param array $tokenEndpointAuthMethodsSupported
     */
    public function setTokenEndpointAuthMethodsSupported(array $tokenEndpointAuthMethodsSupported)
    {
        $this->token_endpoint_auth_methods_supported = $tokenEndpointAuthMethodsSupported;
    }

    /**
     * @return array
     */
    public function getTokenEndpointAuthSigningAlgValuesSupported(): array
    {
        return $this->token_endpoint_auth_signing_alg_values_supported;
    }

    /**
     * @param array $tokenEndpointAuthSigningAlgValuesSupported
     */
    public function setTokenEndpointAuthSigningAlgValuesSupported(array $tokenEndpointAuthSigningAlgValuesSupported)
    {
        $this->token_endpoint_auth_signing_alg_values_supported = $tokenEndpointAuthSigningAlgValuesSupported;
    }

    /**
     * @return array
     */
    public function getRequestObjectSigningAlgValuesSupported(): array
    {
        return $this->request_object_signing_alg_values_supported;
    }

    /**
     * @param array $requestObjectSigningAlgValuesSupported
     */
    public function setRequestObjectSigningAlgValuesSupported(array $requestObjectSigningAlgValuesSupported)
    {
        $this->request_object_signing_alg_values_supported = $requestObjectSigningAlgValuesSupported;
    }

    /**
     * @return array
     */
    public function getUiLocalesSupported(): array
    {
        return $this->ui_locales_supported;
    }

    /**
     * @param array $uiLocalesSupported
     */
    public function setUiLocalesSupported(array $uiLocalesSupported)
    {
        $this->ui_locales_supported = $uiLocalesSupported;
    }

    /**
     * @return bool
     */
    public function isRequestParameterSupported(): bool
    {
        return $this->request_parameter_supported;
    }

    /**
     * @param bool $requestParameterSupported
     */
    public function setRequestParameterSupported(bool $requestParameterSupported)
    {
        $this->request_parameter_supported = $requestParameterSupported;
    }

    /**
     * @return array
     */
    public function getIdTokenSigningAlgValuesSupported(): array
    {
        return $this->id_token_signing_alg_values_supported;
    }

    /**
     * @param array $idTokenSigningAlgValuesSupported
     */
    public function setIdTokenSigningAlgValuesSupported(array $idTokenSigningAlgValuesSupported)
    {
        $this->id_token_signing_alg_values_supported = $idTokenSigningAlgValuesSupported;
    }

    /**
     * @return array
     */
    public function getIdTokenEncryptionEncValuesSupported(): array
    {
        return $this->id_token_encryption_enc_values_supported;
    }

    /**
     * @param array $idTokenEncryptionEncValuesSupported
     */
    public function setIdTokenEncryptionEncValuesSupported(array $idTokenEncryptionEncValuesSupported)
    {
        $this->id_token_encryption_enc_values_supported = $idTokenEncryptionEncValuesSupported;
    }

    /**
     * @return array|NULL
     */
    public function getUserInfoSigningAlgValuesSupported()
    {
        return $this->userinfo_signing_alg_values_supported;
    }

    /**
     * @param array|NULL $userInfoSigningAlgValuesSupported
     */
    public function setUserInfoSigningAlgValuesSupported(array $userInfoSigningAlgValuesSupported = null)
    {
        $this->userinfo_signing_alg_values_supported = $userInfoSigningAlgValuesSupported;
    }

    /**
     * @return array|NULL
     */
    public function getUserInfoEncryptionAlgValuesSupported()
    {
        return $this->userinfo_encryption_alg_values_supported;
    }

    /**
     * @param array|NULL $userInfoEncryptionAlgValuesSupported
     */
    public function setUserInfoEncryptionAlgValuesSupported(array $userInfoEncryptionAlgValuesSupported = null)
    {
        $this->userinfo_encryption_alg_values_supported = $userInfoEncryptionAlgValuesSupported;
    }

    /**
     * @return array
     */
    public function getDisplayValuesSupported(): array
    {
        return $this->display_values_supported;
    }

    /**
     * @param array $displayValuesSupported
     */
    public function setDisplayValuesSupported(array $displayValuesSupported)
    {
        $this->display_values_supported = $displayValuesSupported;
    }

    /**
     * @return array
     */
    public function getClaimTypesSupported(): array
    {
        return $this->claim_types_supported;
    }

    /**
     * @param array $claimTypesSupported
     */
    public function setClaimTypesSupported(array $claimTypesSupported)
    {
        $this->claim_types_supported = $claimTypesSupported;
    }

    /**
     * @return array
     */
    public function getClaimsSupported(): array
    {
        return $this->claims_supported;
    }

    /**
     * @param array $claimsSupported
     */
    public function setClaimsSupported(array $claimsSupported)
    {
        $this->claims_supported = $claimsSupported;
    }

    /**
     * @return bool
     */
    public function isClaimsParameterSupported(): bool
    {
        return $this->claims_parameter_supported;
    }

    /**
     * @param bool $claimsParameterSupported
     */
    public function setClaimsParameterSupported(bool $claimsParameterSupported)
    {
        $this->claims_parameter_supported = $claimsParameterSupported;
    }

    /**
     * @return bool
     */
    public function isFrontChannelLogoutSupported(): bool
    {
        return $this->front_channel_logout_supported;
    }

    /**
     * @param bool $frontChannelLogoutSupported
     */
    public function setFrontChannelLogoutSupported(bool $frontChannelLogoutSupported)
    {
        $this->front_channel_logout_supported = $frontChannelLogoutSupported;
    }

    /**
     * @return bool
     */
    public function isBackChannelLogoutSupported(): bool
    {
        return $this->back_channel_logout_supported;
    }

    /**
     * @param bool $backChannelLogoutSupported
     */
    public function setBackChannelLogoutSupported(bool $backChannelLogoutSupported)
    {
        $this->back_channel_logout_supported = $backChannelLogoutSupported;
    }

    /**
     * @return bool
     */
    public function isRequestUriParameterSupported(): bool
    {
        return $this->request_uri_parameter_supported;
    }

    /**
     * @param bool $requestUriParameterSupported
     */
    public function setRequestUriParameterSupported(bool $requestUriParameterSupported)
    {
        $this->request_uri_parameter_supported = $requestUriParameterSupported;
    }

    /**
     * @return bool
     */
    public function isRequireRequestUriRegistration(): bool
    {
        return $this->require_request_uri_registration;
    }

    /**
     * @param bool $requireRequestUriRegistration
     */
    public function setRequireRequestUriRegistration(bool $requireRequestUriRegistration)
    {
        $this->require_request_uri_registration = $requireRequestUriRegistration;
    }

    /**
     * @return int
     */
    public function getTlsClientCertificateBoundAccessTokens(): int
    {
        return $this->tls_client_certificate_bound_access_tokens;
    }

    /**
     * @param int $tlsClientCertificateBoundAccessTokens
     */
    public function setTlsClientCertificateBoundAccessTokens(int $tlsClientCertificateBoundAccessTokens)
    {
        $this->tls_client_certificate_bound_access_tokens = $tlsClientCertificateBoundAccessTokens;
    }

    /**
     * @return int
     */
    public function getRequestUriQuota(): int
    {
        return $this->request_uri_quota;
    }

    /**
     * @param int $requestUriQuota
     */
    public function setRequestUriQuota(int $requestUriQuota)
    {
        $this->request_uri_quota = $requestUriQuota;
    }

    /**
     * Retrieves all openId Config Data fetched from authority
     *
     * @param bool $inJsonFormat flag determining whether openId config data shall be retrieved in json or array format
     *
     * @return array|string
     */
    public function getData($inJsonFormat = true)
    {
        if ($inJsonFormat) {
            return json_encode($this->openIdConfigData);
        }

        return $this->openIdConfigData;
    }

    /**
     * Initializes openId Config class properties with given array data
     *
     * @param array $openIdConfigData
     */
    private function init(array $openIdConfigData)
    {
        $this->openIdConfigData = $openIdConfigData;

        foreach ($openIdConfigData as $key => $value) {
            if (property_exists(OpenIdConfig::class, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
