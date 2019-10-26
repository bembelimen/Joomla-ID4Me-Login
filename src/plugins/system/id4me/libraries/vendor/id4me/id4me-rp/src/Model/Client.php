<?php

namespace Id4me\RP\Model;

/**
 * Container class responsible of encapsulating OpenId Client Data provided by openId Authority
 *
 * @package Id4me\RP\Model
 */
class Client
{
    /**
     * issuer
     *
     * @var string
     */
    protected $issuer = null;

    /**
     * clientName
     *
     * @var string
     */
    protected $clientName = null;

    /**
     * clientId
     *
     * @var string
     */
    protected $clientId = null;

    /**
     * clientSecret
     *
     * @var string
     */
    protected $clientSecret = null;

    /**
     * Client credentials expiration
     *
     * @var int
     */
    protected $clientExpirationTime = 0;

    /**
     * Current active redirect uri used by client
     *
     * @var string
     */
    protected $activeRedirectUri = null;

    /**
     * List of all consumer redirect uris registered by authority
     *
     * @var array
     */
    protected $redirectUris = [];

    /**
     * Algorithm for signed User Info reponse
     *
     * @var string
     */
    protected $userInfoSignedResponseAlg = null;

    /**
     * Initializes an instance of Client
     *
     * @param $issuer
     */
    public function __construct($issuer)
    {
        $this->issuer = $issuer;
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
    public function getClientName(): string
    {
        return $this->clientName;
    }

    /**
     * @param string $clientName
     */
    public function setClientName(string $clientName)
    {
        $this->clientName = $clientName;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret(string $clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return int
     */
    public function getClientExpirationTime(): int
    {
        return $this->clientExpirationTime;
    }

    /**
     * @param int $clientExpirationTime
     */
    public function setClientExpirationTime(int $clientExpirationTime)
    {
        $this->clientExpirationTime = $clientExpirationTime;
    }

    /**
     * @return string
     */
    public function getActiveRedirectUri(): string
    {
        return $this->activeRedirectUri;
    }

    /**
     * @param string $activeRedirectUri
     */
    public function setActiveRedirectUri(string $activeRedirectUri)
    {
        $this->activeRedirectUri = $activeRedirectUri;
    }

    /**
     * @return array
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * @param array $redirectUris
     */
    public function setRedirectUris(array $redirectUris)
    {
        $this->redirectUris = $redirectUris;
    }


    /**
     * @return string|NULL
     */
    public function getUserInfoSignedResponseAlg()
    {
        return $this->userInfoSignedResponseAlg;
    }

    /**
     * @param string|NULL $userInfoSignedResponseAlg
     */
    public function setUserInfoSignedResponseAlg(string $userInfoSignedResponseAlg = null)
    {
        $this->userInfoSignedResponseAlg = $userInfoSignedResponseAlg;
    }
}
