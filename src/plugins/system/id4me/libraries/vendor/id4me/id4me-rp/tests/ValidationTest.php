<?php

use Id4me\RP\Model\JWT;
use Id4me\RP\Exception\InvalidAuthorityIssuerException;
use Id4me\RP\Exception\InvalidJWTTokenException;
use Id4me\RP\Exception\InvalidIDTokenException;
use Id4me\RP\Validation;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for Validation::validateJWTTokenSignature()
     *
     * @param string  $usedKey
     * @param string  $algorithm
     * @param JWT $token
     * @param array   $jwksArray
     * @param string  $exceptionMessage
     *
     * @throws InvalidIDTokenException
     *
     * @dataProvider getJWTTokenDataForSignatureValidation()
     */
    public function testValidateJWTTokenSignature(
        string $usedKey,
        string $algorithm,
        JWT $token,
        array $jwksArray,
        string $expectedErrorMsg,
        string $expectedExceptionType
    ) {
        $thrownExceptionMsg  = '';
        $thrownExceptionType = '';

        try {
            $validation = new Validation();
            $validation->validateJWTTokenSignature(
                $usedKey,
                $algorithm,
                $token,
                $jwksArray
            );
        } catch (Exception $e) {
            $thrownExceptionMsg  = $e->getMessage();
            $thrownExceptionType = get_class($e);
        }
        $this->assertEquals($thrownExceptionMsg, $expectedErrorMsg);
        $this->assertEquals($thrownExceptionType, $expectedExceptionType);
    }

    /**
     * Retrieves data to test validateIdTokenSignature
     *
     * @return array
     */
    public function getJWTTokenDataForSignatureValidation(): array
    {
        $idToken = new JWT('eyJraWQiOiJGT3Z5IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiIrOW4xY1J1akxWVXcwbGU3SzRMdGw0bjBZNDVHalpuWWtHRkpPbERhdDBBQ1pFMXNHOVp6TlV2QWRmK2t1dGx0IiwiYXVkIjoiN2Z0ajZ2dzU0NXN1ayIsImlkNG1lLmlkZW50aWZpZXIiOiJzbWlsZXkubGFuZCIsImFtciI6WyJwd2QiXSwiaXNzIjoiaHR0cHM6XC9cL2lkLnRlc3QuZGVuaWMuZGUiLCJleHAiOjE1NTYxMTY5OTYsImlhdCI6MTU1NjExNjA5Nn0.DkrevqYO-MFCZh38HF9Hs4uRn37sxG4IjvY0XYihQq72iaWoLVz5VHt6-uxWXJ3WQYiZDDOTm55hvDr37iO9jNIVUBV0mmnF5RAHZx7tllgTWzFek2TPCLu9OItiKJJx-ByqKm-Zm-NZvrDbj90xtZEnVZLk8mrPHRAoc8KvTmZ69iCGlb-2Rpood1vIqakDbz2MjBnypcI_Sh_xmISfdK-5r7SK-HUxeSMFOnYEp5Ou1IRaTk2n_z0usDX-Do0yPGNl5MMfOlB4wHayuUP8i0-zvOvqf0mGXc-_xyDvoUly-hDZ-XMmVE_iV-PdNXsrkV90SW5O27M6c4rJLNNw3g');
        $jwks    = json_decode(file_get_contents(sprintf('%s/mocks/jwks.json', __DIR__)), true);

        return [
            [
                'FOvy',
                'RS256',
                $idToken,
                $jwks,
                '',
                ''
            ],
            [
                'FOvy',
                'RS256',
                new JWT('t.e.s.t'),
                $jwks,
                Validation::INVALID_JWT_TOKEN_SIGNATURE_EXCEPTION,
                get_class(new InvalidJWTTokenException)
            ],
            [
                'wrong-key',
                'RS256',
                $idToken,
                $jwks,
                Validation::INVALID_JWT_TOKEN_JWS_KEYSET_KEY_NOT_FOUND,
                get_class(new InvalidJWTTokenException)
            ],
            [
                'FOvy',
                'wrong-algo',
                $idToken,
                $jwks,
                sprintf(Validation::UNSUPPORTED_JWT_TOKEN_ALGORITHM_EXCEPTION, 'wrong-algo'),
                get_class(new InvalidJWTTokenException)
            ]
        ];
    }

    /**
     * Test for Validation::validateISS()
     *
     * @param string $originIssuer
     * @param string $deliveredIssuer
     * @param bool   $exactMatch
     * @param bool   $throwsException
     *
     * @throws InvalidAuthorityIssuerException
     *
     * @dataProvider getJWTTokenDataForISSValidation()
     */
    public function testValidateJWTTokenISS(
        string $originIssuer,
        string $deliveredIssuer,
        bool $exactMatch,
        string $expectedErrorMsg,
        string $expectedExceptionType
    ) {
        $thrownExceptionMsg  = '';
        $thrownExceptionType = '';

        try {
            $validation = new Validation();
            $validation->validateISS(
                $originIssuer,
                $deliveredIssuer,
                $exactMatch
            );
        } catch (Exception $e) {
            $thrownExceptionMsg  = $e->getMessage();
            $thrownExceptionType = get_class($e);
        }
        $this->assertEquals($thrownExceptionMsg, $expectedErrorMsg);
        $this->assertEquals($thrownExceptionType, $expectedExceptionType);
    }

    /**
     * Retrieves data to test validateISS
     *
     * @return array
     */
    public function getJWTTokenDataForISSValidation(): array
    {
        return [
            [
                'https://id.test.denic.de',
                'https://id.test.denic.de',
                true,
                '',
                ''
            ],
            [
                'id.test.denic.de',
                'https://id.test.denic.de',
                false,
                '',
                ''
            ],
            [
                'https://id.test.denic.de',
                'id.test.denic.de',
                true,
                Validation::INVALID_ISSUER_EXCEPTION,
                get_class(new InvalidAuthorityIssuerException)
            ],
            [
                'https://id.test.denic.de',
                'https://id.test.denic.com',
                true,
                Validation::INVALID_ISSUER_EXCEPTION,
                get_class(new InvalidAuthorityIssuerException)
            ]
        ];
    }

    /**
     * Test for Validation::validateAudience()
     *
     * @param        $audience
     * @param string $authorizedParty
     * @param string $clientId
     * @param bool   $throwsException
     *
     * @throws InvalidIDTokenException
     *
     * @dataProvider getIdTokenDataForAudienceValidation
     */
    public function testValidateIDTokenAudience(
        $audience,
        string $authorizedParty,
        string $clientId,
        string $expectedErrorMsg,
        string $expectedExceptionType
    ) {
        $thrownExceptionMsg  = '';
        $thrownExceptionType = '';
        try {
            $validation = new Validation();
            $validation->validateAudience(
                $audience,
                $authorizedParty,
                $clientId
            );
        } catch (Exception $e) {
            $thrownExceptionMsg  = $e->getMessage();
            $thrownExceptionType = get_class($e);
        }
        $this->assertEquals($thrownExceptionMsg, $expectedErrorMsg);
        $this->assertEquals($thrownExceptionType, $expectedExceptionType);
    }

    /**
     * Retrieves data to test validateAudience
     *
     * @return array
     */
    public function getIdTokenDataForAudienceValidation(): array
    {
        return [
            [
                '7ftj6vw545suk',
                '',
                '7ftj6vw545suk',
                '',
                ''
            ],
            [
                '7ftj6vw545suk',
                '7ftj6vw545suk',
                '7ftj6vw545suk',
                '',
                ''
            ],
            [
                '7ftj6vw545suk',
                '',
                'wrong-client-id',
                Validation::INVALID_JWT_TOKEN_AUDIENCE_EXCEPTION,
                get_class(new InvalidIDTokenException)
            ],
            [
                ['7ftj6vw545suk', 'garbage', 'foo', 'bar'],
                '7ftj6vw545suk',
                '7ftj6vw545suk',
                '',
                ''
            ],
            [
                false,
                '',
                '7ftj6vw545suk',
                Validation::INVALID_JWT_TOKEN_AUDIENCE_EXCEPTION,
                get_class(new InvalidIDTokenException)
            ],
            [
                '',
                '7ftj6vw545suk',
                '7ftj6vw545suk',
                Validation::INVALID_JWT_TOKEN_AUDIENCE_EXCEPTION,
                get_class(new InvalidIDTokenException)
            ],
            [
                [],
                'vwsvw5',
                '7ftj6vw545suk',
                Validation::INVALID_JWT_TOKEN_AUDIENCE_EXCEPTION,
                get_class(new InvalidIDTokenException)
            ]
        ];
    }

    /**
     * Test for Validation::validateExpirationTime()
     *
     * @param string $expirationTime
     * @param bool   $throwsException
     *
     * @throws InvalidIDTokenException
     *
     * @dataProvider getIdTokenDataForExpirationTimeValidation
     */
    public function testValidateIDTokenExpirationTime(
        string $expirationTime,
        string $expectedErrorMsg,
        string $expectedExceptionType
    ) {
        $thrownExceptionMsg  = '';
        $thrownExceptionType = '';
        try {
            $validation = new Validation();
            $validation->validateExpirationTime(
                $expirationTime
            );
        } catch (Exception $e) {
            $thrownExceptionMsg  = $e->getMessage();
            $thrownExceptionType = get_class($e);
        }
        $this->assertEquals($thrownExceptionMsg, $expectedErrorMsg);
        $this->assertEquals($thrownExceptionType, $expectedExceptionType);
    }

    /**
     * Retrieves data to test validateAudience
     *
     * @return array
     */
    public function getIdTokenDataForExpirationTimeValidation(): array
    {
        return [
            [
                (string) mktime(10, 30, 30, 1, 1, 2037),
                '',
                ''
            ],
            [
                '',
                Validation::INVALID_JWT_TOKEN_EXPIRATION_TIME_EXCEPTION,
                get_class(new InvalidIDTokenException)

            ],
            [
                (string) mktime(10, 30, 30, 1, 1, 1950),
                Validation::INVALID_JWT_TOKEN_EXPIRATION_TIME_EXCEPTION,
                get_class(new InvalidIDTokenException)
            ]
        ];
    }

    /**
     * Test for IdToken Model
     */
    public function testIdTokenInstance()
    {
        $idToken = new JWT('eyJraWQiOiJGT3Z5IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiIrOW4xY1J1akxWVXcwbGU3SzRMdGw0bjBZNDVHalpuWWtHRkpPbERhdDBBQ1pFMXNHOVp6TlV2QWRmK2t1dGx0IiwiYXVkIjoiN2Z0ajZ2dzU0NXN1ayIsImlkNG1lLmlkZW50aWZpZXIiOiJzbWlsZXkubGFuZCIsImFtciI6WyJwd2QiXSwiaXNzIjoiaHR0cHM6XC9cL2lkLnRlc3QuZGVuaWMuZGUiLCJleHAiOjE1NTYxMTY5OTYsImlhdCI6MTU1NjExNjA5Nn0.DkrevqYO-MFCZh38HF9Hs4uRn37sxG4IjvY0XYihQq72iaWoLVz5VHt6-uxWXJ3WQYiZDDOTm55hvDr37iO9jNIVUBV0mmnF5RAHZx7tllgTWzFek2TPCLu9OItiKJJx-ByqKm-Zm-NZvrDbj90xtZEnVZLk8mrPHRAoc8KvTmZ69iCGlb-2Rpood1vIqakDbz2MjBnypcI_Sh_xmISfdK-5r7SK-HUxeSMFOnYEp5Ou1IRaTk2n_z0usDX-Do0yPGNl5MMfOlB4wHayuUP8i0-zvOvqf0mGXc-_xyDvoUly-hDZ-XMmVE_iV-PdNXsrkV90SW5O27M6c4rJLNNw3g');

        // Class name test
        $this->assertInstanceOf(JWT::class, $idToken);

        // Check for variables with value null
        $this->assertNotNull($idToken->getOriginalHeader());
        $this->assertNotNull($idToken->getOriginalBody());
        $this->assertNotNull($idToken->getOriginalSignature());

        $this->assertNotNull($idToken->getDecodedHeader());
        $this->assertNotNull($idToken->getDecodedBody());
        $this->assertNotNull($idToken->getDecodedSignature());

        // getHeaderValue() check
        $this->assertSame(
            'RS256', $idToken->getHeaderValue('alg')
        );

        // getBodyValue() check
        $this->assertSame(
            '7ftj6vw545suk', $idToken->getBodyValue('aud')
        );


        // Header data value test
        $this->assertTrue(is_array($idToken->getDecodedHeader()));
        $this->assertSame(
            $idToken->getDecodedHeader(),
            [
                'kid' => 'FOvy',
                'alg' => 'RS256',
            ]
        );

        // Body data value test
        $this->assertTrue(is_array($idToken->getDecodedBody()));
        $this->assertSame(
            $idToken->getDecodedBody(),
            [
                'sub'              => '+9n1cRujLVUw0le7K4Ltl4n0Y45GjZnYkGFJOlDat0ACZE1sG9ZzNUvAdf+kutlt',
                'aud'              => '7ftj6vw545suk',
                'id4me.identifier' => 'smiley.land',
                'amr'              => ['pwd'],
                'iss'              => 'https://id.test.denic.de',
                'exp'              => 1556116996,
                'iat'              => 1556116096,
            ]
        );
    }
}
