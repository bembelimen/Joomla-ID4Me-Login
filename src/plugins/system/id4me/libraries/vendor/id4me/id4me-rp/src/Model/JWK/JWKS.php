<?php
namespace Id4me\RP\Model\JWK;

use Id4me\RP\Exception\InvalidJWKSException;

/**
 * Class representing a collection of security keys, decoded from JWKS representation
 *
 * @package Id4me\RP\Model\JWK
 */
class JWKS
{
    const INVALID_JWS_KEYSET_STRUCTURE = 'Invalid JWKS structure';

    private $keys = [];

    /**
     * Constructs a JKWS object from JWKS array representation
     *
     * @param array $jwksArray
     * @param array|NULL $keyTypes key types to import
     * @throws InvalidJWKSException
     */
    public function __construct(array $jwksArray, array $keyTypes = null)
    {
        // Load jwks and init the correct key from 3rd party
        if (array_key_exists('keys', $jwksArray) && is_array($jwksArray['keys'])) {
            foreach ($jwksArray['keys'] as $key) {
                if (isset($key['kid'])
                    && isset($key['kty'])
                    && ($keyTypes == null || in_array($key['kty'], $keyTypes)
                    )
                ) {
                    $this->keys[$key['kid']] = JWK::getKeyFromJWK($key);
                }
            }
        } else {
            throw new InvalidJWKSException(self::INVALID_JWS_KEYSET_STRUCTURE);
        }
    }

    /**
     * Returns a key by kid
     * @param string $kid
     *
     * @return JWK|NULL
     */
    public function getKey(string $kid)
    {
        if (array_key_exists($kid, $this->keys)) {
            return $this->keys[$kid];
        } else {
            return null;
        }
    }
}
