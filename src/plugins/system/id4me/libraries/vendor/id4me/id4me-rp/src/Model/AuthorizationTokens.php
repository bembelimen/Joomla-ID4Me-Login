<?php
namespace Id4me\RP\Model;

/**
 * Class representing the authorization tokens returned by Identity Authority /token endpoint
 *
 * @package Id4me\RP\Model
 */
class AuthorizationTokens
{

    /**
     * @var array accessTokenData as provided from Response
     */
    protected $authorizationData;

    /**
     *
     * @var IdToken encoded id_token
     */
    protected $id_token_decoded;

    /**
     * @var string accessToken (access_token)
     */
    protected $access_token;

    /**
     * @var string refreshToken (access_token)
     */
    protected $refresh_token;

    /**
     * @var string scope (scope)
     */
    protected $scope;

    /**
     * @var string idToken (scope)
     */
    protected $id_token;

    /**
     * @var string tokenType (token_type)
     */
    protected $token_type;

    /**
     * @var integer Expire in Timestamp (expires_in)
     */
    protected $expires_in;

    /**
     * @return array|null authorizationData
     */
    public function getAuthorizationData(): array
    {
        return $this->authorizationData;
    }

    /**
     * return authorizationData as Array
     *
     * @param array $authorizationData
     */
    public function setAuthorizationData(array $authorizationData)
    {
        $this->authorizationData = $authorizationData;
    }

    /**
     * return Decoded IdToken Object
     *
     * @return IdToken
     */
    public function getIdTokenDecoded(): IdToken
    {
        return $this->id_token_decoded;
    }

    /**
     * set IdToken Object
     *
     * @param IdToken $idTokenDecoded
     */
    public function setIdTokenDecoded(IdToken $idTokenDecoded)
    {
        $this->id_token_decoded = $idTokenDecoded;
    }

    /**
     * return access Token
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    /**
     * set access Token
     *
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken)
    {
        $this->access_token = $accessToken;
    }

    /**
     * return refresh Token
     *
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    /**
     * return refresh Token
     *
     * @param string $refreshToken
     */
    public function setRefreshToken(string $refreshToken)
    {
        $this->refresh_token = $refreshToken;
    }

    /**
     * return scope
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * set scope
     *
     * @param string $scope
     */
    public function setScope(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * return id Token (encoded string)
     *
     * @return string
     */
    public function getIdToken(): string
    {
        return $this->id_token;
    }

    /**
     * set id Token (encoded string)
     *
     * @param string $idToken
     */
    public function setIdToken(string $idToken)
    {
        $this->id_token = $idToken;
    }

    /**
     * return token Type (eg. Bearer )
     *
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->token_type;
    }

    /**
     * set token Type
     *
     * @param string $tokenType
     */
    public function setTokenType(string $tokenType)
    {
        $this->token_type = $tokenType;
    }

    /**
     * return expiresIn
     *
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expires_in;
    }

    /**
     * set ExpiresIn
     *
     * @param int $expiresIn
     */
    public function setExpiresIn(int $expiresIn)
    {
        $this->expires_in = $expiresIn;
    }

    /**
     * AuthorizationTokens constructor.
     *
     * @param array $authorizationData
     */
    public function __construct(array $authorizationData)
    {
        $this->init($authorizationData);
    }

    /**
     * Initializes AuthorizationTokens class properties with given array data
     *
     * @param array $authorizationData
     */
    private function init(array $authorizationData)
    {
        $this->authorizationData = $authorizationData;

        foreach ($authorizationData as $key => $value) {
            if (property_exists(AuthorizationTokens::class, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
