<?php

use \Id4me\RP\Model\OpenIdConfig;

class OpenIdConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testInit()
    {
        $config = [
            "issuer" => "http://local.host"
        ];

        $c = new OpenIdConfig($config);
        $this->assertEquals($c->getIssuer(), $config["issuer"]);
    }

    public function testInitComplex()
    {
        $config = [
            "issuer" => "http://local.host",
            "token_endpoint" => "http://local.host/token",
            "userinfo_endpoint" => "http://local.host/userinfo",
            "userinfo_signing_alg_values_supported" => ['signing_algorithm_1','signing_algorithm_2'],
            "userinfo_encryption_alg_values_supported" => ['encryption_algorithm_1','encryption_algorithm_2'],
            "not_existing_key" => "it_should_not_throw_error"
        ];

        $c = new OpenIdConfig($config);
        $this->assertEquals($c->getTokenEndpoint(), $config["token_endpoint"]);
        $this->assertEquals($c->getUserInfoEndpoint(), $config["userinfo_endpoint"]);
        $this->assertEquals($c->getUserInfoSigningAlgValuesSupported(), $config["userinfo_signing_alg_values_supported"]);
        $this->assertEquals($c->getUserInfoEncryptionAlgValuesSupported(), $config["userinfo_encryption_alg_values_supported"]);
    }
}
