<?php

use Id4me\RP\Discovery;
use Id4me\RP\Exception\InvalidOpenIdDomainException;
use Id4me\RP\Exception\OpenIdDnsRecordNotFoundException;
use Id4me\RP\Helper\OpenIdConfigHelper;
use Id4me\Test\Mock\HttpClientGuzzle;
use Id4me\RP\Model\Client;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\Service;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for Service::discover()
     *
     * @param string $identifier
     * @param string $expectedAuthorityName
     * @param Exception|null $expectedException
     *
     * @dataProvider getTestDiscoverData()
     *
     * @throws InvalidOpenIdDomainException if given consumerId is an invalid domain
     * @throws OpenIdDnsRecordNotFoundException if no matching openId DNS record is found
     */
    public function testDiscover(
        $identifier,
        $expectedAuthorityName,
        Exception $expectedException = null
    ) {
        $service = new Service(new HttpClientGuzzle());

        $service->setDiscovery(
            $this->getDiscovery($expectedException)
        );

        if (! empty($expectedException)) {
            $this->expectException(get_class($expectedException));
        }

        $this->assertEquals($expectedAuthorityName, $service->discover($identifier));
    }

    /**
     * Test for Service::register()
     */
    public function testRegister()
    {
        $service = new Service(new HttpClientGuzzle());
        $client  = $this->createClient();

        $retrievedClient = $service->register(
            $this->getOpenIdConfig(),
            'rezepte-elster.de',
            'https://rezepte-elster.de'
        );

        $client->setClientSecret($retrievedClient->getClientSecret());
        $client->setClientId($retrievedClient->getClientId());

        $this->assertEquals($client, $retrievedClient);
    }

    /**
     * Test for Service::getAuthorizationUrl()
     */
    public function testGetAuthorizationUrl()
    {
        $service = new Service(new HttpClientGuzzle());

        $this->assertRegExp(
            '/^https:\/\/id.denic.de\/login\?client_id=clientId&login_hint=rezepte-elster\.de&redirect_uri=.*rezepte-elster\.de.*$/',
            $service->getAuthorizationUrl(
                $this->getOpenIdConfig(),
                'clientId',
                'rezepte-elster.de',
                'https://rezepte-elster.de',
                'test'
            )
        );
    }

    /**
     * @return array
     */
    public function getTestDiscoverData()
    {
        return [
            ['', null, new InvalidOpenIdDomainException()],
            [true, null, new InvalidOpenIdDomainException()],
            [false, null, new InvalidOpenIdDomainException()],
            [1, null, new InvalidOpenIdDomainException()],
            [-1, null, new InvalidOpenIdDomainException()],
            ['domain', null, new InvalidOpenIdDomainException()],
            ['google.com', null, new InvalidOpenIdDomainException()],
            ['rezepte-elster.de', null, new OpenIdDnsRecordNotFoundException()],
            ['rezepte-elster.de', 'id.denic.de', null]
        ];
    }

    /**
     * Retrieves an instance of Discovery depending on given parameter
     *
     * @param Exception $expectedException
     *
     * @return Discovery
     *
     * @throws Exception
     */
    private function getDiscovery(Exception $expectedException = null)
    {
        if (empty($expectedException)) {
            return new Discovery();
        }

        $discovery = $this->getMockBuilder('Id4me\RP\Discovery')
                          ->disableOriginalConstructor()
                          ->setMethods(['getOpenIdDnsRecord'])
                          ->getMock();

        $discovery->expects($this->any())
                  ->method('getOpenIdDnsRecord')
                  ->willThrowException($expectedException);


        return $discovery;
    }

    /**
     * Retrieves an instance of OpenIdConfig from fetched json config data
     *
     * @return OpenIdConfig
     */
    private function getOpenIdConfig()
    {
        return OpenIdConfigHelper::instance()->createFromJson(
            file_get_contents(
                sprintf('%s/mocks/openIdConfigJson.json', __DIR__)
            )
        );
    }

    /**
     * Creates a client
     *
     * @return Client
     */
    private function createClient()
    {
        $client = new Client('https://id.denic.de');

        $client->setClientName('rezepte-elster.de');
        $client->setClientId('clientId');
        $client->setClientSecret('clientSecret');
        $client->setActiveRedirectUri('https://rezepte-elster.de');
        $client->setRedirectUris(['https://rezepte-elster.de']);
        $client->setUserInfoSignedResponseAlg('RS256');

        return $client;
    }

}
