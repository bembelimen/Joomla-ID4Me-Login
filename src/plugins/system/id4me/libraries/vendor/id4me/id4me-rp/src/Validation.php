<?php
namespace Id4me\RP;

use Id4me\RP\Exception\InvalidAuthorityIssuerException;
use Id4me\RP\Exception\InvalidIDTokenException;
use Id4me\RP\Exception\InvalidJWTTokenException;
use Id4me\RP\Model\JWT;
use Id4me\RP\Model\IdToken;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\Exception\ValidationException;
use Id4me\RP\Model\JWK\JWKS;
use Id4me\RP\Exception\InvalidUserInfoException;

/**
 * This class is responsible of providing utility function in order to validate authority data
 *
 * Following validation use cases are covered here:
 *
 * - ID Token:
 * solely following requirements of ID4Me specifications 4.5.3. ID Token Validation: 1. to 5. and 9.
 */
class Validation
{

    const INVALID_ISSUER_EXCEPTION = 'iss and issuer values are not equals';

    const INVALID_JWT_TOKEN_AUDIENCE_EXCEPTION = 'Authority audience does not contains clientId';

    const INVALID_JWT_TOKEN_EXPIRATION_TIME_EXCEPTION = 'Invalid ID Token expiration time';

    const INVALID_JWT_TOKEN_SIGNATURE_EXCEPTION = 'Invalid ID Token Signature provided';

    const UNSUPPORTED_JWT_TOKEN_ALGORITHM_EXCEPTION = 'Invalid algorithm: %s, only RS256 supported currently';

    const INVALID_JWT_TOKEN_JWS_KEYSET_KEY_NOT_FOUND = 'Used key not found in JWKS for signature verification';

    const INVALID_ID_TOKEN_STRUCTURE = 'Invalid ID Token Structure';

    /**
     * Validates ID token
     *
     * @param IdToken $idToken
     *            ID token to check
     * @param OpenIdConfig $openIdConfig
     *            Open ID config of the Authority
     * @param array $jwksArray
     *            Public keys of the authority
     * @param string $clientId
     *            Client ID
     *
     * @throws InvalidIDTokenException if any of required validations fails
     */
    public function validateIdToken(IdToken $idToken, OpenIdConfig $openIdConfig, array $jwksArray, string $clientId)
    {
        try {
            $this->validateJWTTokenSignature(
                $idToken->getHeaderValue('kid'),
                $idToken->getHeaderValue('alg'),
                $idToken,
                $jwksArray
            );

            $this->validateISS($idToken->getBodyValue('iss'), $openIdConfig->getIssuer());

            $this->validateAudience($idToken->getBodyValue('aud'), $idToken->getBodyValue('azp'), $clientId);

            $this->validateExpirationTime($idToken->getBodyValue('exp'));
        } catch (ValidationException $e) {
            throw new InvalidIDTokenException($e->getMessage());
        }
    }

    /**
     * Validates User info
     *
     * @param JWT $userInfo
     *            User info as JWT
     *            ID token to check
     * @param OpenIdConfig $openIdConfig
     *            Open ID config of the Issuer
     * @param array $jwksArray
     *            Public keys of the authority
     * @param string $clientId
     *            Client ID
     *
     * @throws InvalidUserInfoException if any of required validations fails
     */
    public function validateUserInfo(JWT $userInfo, OpenIdConfig $openIdConfig, array $jwksArray, $clientId)
    {
        try {
            $this->validateJWTTokenSignature(
                $userInfo->getHeaderValue('kid'),
                $userInfo->getHeaderValue('alg'),
                $userInfo,
                $jwksArray
            );

            $this->validateISS($userInfo->getBodyValue('iss'), $openIdConfig->getIssuer());

            if ($userInfo->getBodyValue('aud') !== null) {
                $this->validateAudience($userInfo->getBodyValue('aud'), $userInfo->getBodyValue('azp'), $clientId);
            }
            if ($userInfo->getBodyValue('exp') !== null) {
                $this->validateExpirationTime($userInfo->getBodyValue('exp'));
            }
        } catch (ValidationException $e) {
            throw new InvalidUserInfoException($e->getMessage());
        }
    }

    /**
     * Validates given authority issuer data
     *
     * Note this this the second validation step described in ID4Me specifications of ID Token Validation
     *
     * @param string $originIssuer
     * @param string $deliveredIssuer
     * @param boolean $exactMatch
     *
     * @throws InvalidAuthorityIssuerException if provided iss and issuer values are not equal
     */
    public function validateISS(string $originIssuer, string $deliveredIssuer, bool $exactMatch = true)
    {
        if ($exactMatch && ($originIssuer !== $deliveredIssuer)) {
            throw new InvalidAuthorityIssuerException(self::INVALID_ISSUER_EXCEPTION);
        }

        if (! $exactMatch && ! preg_match('/https?:\/\/' . preg_quote($originIssuer, '/') . '\/?/', $deliveredIssuer)) {
            throw new InvalidAuthorityIssuerException(self::INVALID_ISSUER_EXCEPTION);
        }
    }

    /**
     * Validates given JWT Token signature using given value and RSA key data
     *
     * @param string $usedKey
     * @param string $algorithm
     * @param JWT $token
     * @param array $jwksArray
     *
     * @throws InvalidJWTTokenException
     */
    public function validateJWTTokenSignature(string $usedKey, string $algorithm, JWT $token, array $jwksArray)
    {
        if ($algorithm != 'RS256') {
            throw new InvalidJWTTokenException(sprintf(self::UNSUPPORTED_JWT_TOKEN_ALGORITHM_EXCEPTION, $algorithm));
        }

        // Retrieve signature and content
        $signature = $token->getDecodedSignature();
        $content = $token->getOriginalHeader() . '.' . $token->getOriginalBody();

        // Only process RSA keys as supported
        $keys = new JWKS($jwksArray, ['RSA']);

        $usedKeyDetails = $keys->getKey($usedKey);

        if ($usedKeyDetails === null) {
            throw new InvalidJWTTokenException(self::INVALID_JWT_TOKEN_JWS_KEYSET_KEY_NOT_FOUND);
        }

        // Verify signature with key
        if (1 !== openssl_verify($content, $signature, $usedKeyDetails->getHandle(), 'sha256')) {
            throw new InvalidJWTTokenException(self::INVALID_JWT_TOKEN_SIGNATURE_EXCEPTION);
        }
    }

    /**
     * Validates audience data in given decrypted token value
     *
     * Validation will be done in accordance to specifications 3., 4. and 5. defined in 4.5.3. ID Token Validation
     *
     * @param mixed $audience
     * @param string|null $authorizedParty
     * @param string $clientId
     *
     * @throws InvalidIDTokenException
     */
    public function validateAudience($audience, $authorizedParty, string $clientId)
    {
        $isValidAudience = false;

        if (! empty($audience)) {
            if (is_array($audience)) {
                $isValidAudience = $this->isValidMultipleAudience($audience, $authorizedParty, $clientId);
            } elseif ($audience == $clientId) {
                $isValidAudience = true;
            } elseif ($audience != $clientId) {
                // check if `azd` matches client-id
                if ($authorizedParty == $clientId) {
                    $isValidAudience = true;
                }
            }
        }

        if (! $isValidAudience) {
            throw new InvalidIDTokenException(self::INVALID_JWT_TOKEN_AUDIENCE_EXCEPTION);
        }
    }

    /**
     * Checks if given audience data in constellation of multiple audience (clientId list) are valid
     *
     * In this case verification is done in accordance to specifications 4. and 5. defined in 4.5.3. ID Token Validation
     *
     * @param array $audience
     * @param string $authorizedParty
     * @param string $clientId
     *
     * @return bool
     */
    public function isValidMultipleAudience(array $audience, string $authorizedParty, string $clientId): bool
    {
        if ((count($audience) <= 1) && (current($audience) == $clientId)) {
            return true;
        }

        if ($authorizedParty && ($authorizedParty != $clientId)) {
            return false;
        }

        return in_array($clientId, $audience);
    }

    /**
     * Validates ID Token expiration time
     *
     * @param string $expirationTime
     *
     * @throws InvalidIDTokenException
     */
    public function validateExpirationTime(string $expirationTime)
    {
        $isValidExpirationTime = false;

        if (! empty($expirationTime)) {
            $isValidExpirationTime = (Validation::getTime() < intval($expirationTime));
        }
        ;

        if (! $isValidExpirationTime) {
            throw new InvalidIDTokenException(self::INVALID_JWT_TOKEN_EXPIRATION_TIME_EXCEPTION);
        }
    }

    private static $forezenTime = null;

    private static function getTime()
    {
        if (Validation::$forezenTime === null) {
            return time();
        } else {
            return Validation::$forezenTime;
        }
    }

    public static function freezeTime($time)
    {
        Validation::$forezenTime = $time;
    }
}
