<?php

use Id4me\RP\Helper\OpenIdConfigHelper;
use Id4me\Test\Mock\HttpClientGuzzle;
use Id4me\RP\Model\Client;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\Registration;

class RegistrationTest extends \PHPUnit\Framework\TestCase
{
    private $registration;
    
    private $client;
    
    private $openidconfig;
    
    public function setUp()
    {
        $this->registration = new Registration(new HttpClientGuzzle());
        
        $this->client = $this->createClient();
        $this->openidconfig = $this->getOpenIdConfig();        
    }
    
    /**
     * Test for Registration::registerClient()
     */
    public function testRegisterClient()
    {
        
        $retrievedClient = $this->registration->register(
            $this->openidconfig,
            'rezepte-elster.de',
            'https://rezepte-elster.de'
        );

        $this->client->setClientSecret($retrievedClient->getClientSecret());
        $this->client->setClientId($retrievedClient->getClientId());

        $this->assertEquals($this->client, $retrievedClient);             
    }

    public function testRegisterClientNoRS256()
    {
        $this->openidconfig->setUserInfoSigningAlgValuesSupported(
            array_diff($this->openidconfig->getUserInfoSigningAlgValuesSupported(), ['RS256']));
        
        $retrievedClient = $this->registration->register(
            $this->openidconfig,
            'rezepte-elster.de',
            'https://rezepte-elster.de'
            );
        
        $this->client->setClientSecret($retrievedClient->getClientSecret());
        $this->client->setClientId($retrievedClient->getClientId());
        $this->client->setUserInfoSignedResponseAlg(NULL);
        
        $this->assertEquals($this->client, $retrievedClient);        

    }
    
    public function testRegisterClientNoUserInfoSigning()
    {
        $this->openidconfig->setUserInfoSigningAlgValuesSupported(NULL);
        
        $retrievedClient = $this->registration->register(
            $this->openidconfig,
            'rezepte-elster.de',
            'https://rezepte-elster.de'
            );
        
        $this->client->setClientSecret($retrievedClient->getClientSecret());
        $this->client->setClientId($retrievedClient->getClientId());       
        $this->client->setUserInfoSignedResponseAlg(NULL);
        
        $this->assertEquals($this->client, $retrievedClient);        
    }

    /**
     * Creates a Client Instance
     *
     * @return Client
     */
    private function createClient()
    {
        $client = new Client('https://id.denic.de');

        $client->setClientName('rezepte-elster.de');
        $client->setClientId('clientId');
        $client->setClientSecret('clientSecret');
        $client->setClientExpirationTime(0);
        $client->setActiveRedirectUri('https://rezepte-elster.de');
        $client->setRedirectUris(['https://rezepte-elster.de']);
        $client->setUserInfoSignedResponseAlg('RS256');

        return $client;
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
}
