<?php

use Id4me\RP\Helper\OpenIdConfigHelper;
use Id4me\RP\Model\OpenIdConfig;

class OpenIdConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateFromJson()
    {
        $input = '{"issuer": "http://local.host"}';

        $openIdConfig = new OpenIdConfig(["issuer" => "http://local.host"]);
        $helper = OpenIdConfigHelper::instance();
        $openIdConfigFromHelper = $helper->createFromJson($input);

        $this->assertEquals($openIdConfig->getIssuer(), $openIdConfigFromHelper->getIssuer());
    }

    public function testCreateFromArray()
    {
        $input = ["issuer" => "http://local.host"];

        $openIdConfig = new OpenIdConfig($input);

        $helper = OpenIdConfigHelper::instance();
        $openIdConfigFromHelper = $helper->createFromArray($input);

        $this->assertEquals($openIdConfig->getIssuer(), $openIdConfigFromHelper->getIssuer());
    }
}
