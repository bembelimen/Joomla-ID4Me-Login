<?php
namespace Id4me\RP\Model\JWK;

use Id4me\RP\Exception\InvalidJWKException;

/**
 * Abstract class representing a security key of any type, decoded from JWK representation
 *
 * @package Id4me\RP\Model\JWK
 */
abstract class JWK
{
    const UNSUPPORTED_KEY_TYPE_EXCEPTION = 'Invalid key type: %s, only RSA supported currently';
    const UNSPECIFIED_KEY_TYPE_EXCEPTION = 'No key type specified';

    /**
     * Constructs key object from JWK representation
     *
     * @param array $jwk
     */
    abstract public function __construct(array $jwk);

    /**
     * Gets specific key representation
     */
    abstract public function getHandle();

    /**
     * Detects key type based on 'kty' property and constructs key object for this key type
     *
     * @param array $jwk
     *
     * @throws InvalidJWKException
     * @return JWK
     */
    public static function getKeyFromJWK(array $jwk): JWK
    {
        if (array_key_exists('kty', $jwk)) {
            if ($jwk['kty'] === 'RSA') {
                return new JWKRSA($jwk);
            } else {
                throw new InvalidJWKException(sprintf(JWK::UNSUPPORTED_KEY_TYPE_EXCEPTION, $jwk['kty']));
            }
        } else {
            throw new InvalidJWKException(JWK::UNSPECIFIED_KEY_TYPE_EXCEPTION, $jwk['kty']);
        }
    }
}
