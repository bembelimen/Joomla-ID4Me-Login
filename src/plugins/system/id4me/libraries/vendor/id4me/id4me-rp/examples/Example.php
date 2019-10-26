<?php
use Id4me\RP\Service;
use Id4me\RP\HttpClient;
use Id4me\Test\Mock\HttpClientGuzzle;
use Id4me\RP\Model\ClaimRequestList;
use Id4me\RP\Model\ClaimRequest;

include __DIR__ . '/../vendor/autoload.php';

/**
 * Class describing how to use provided Id4Me mechanisms supported by current library
 */
class Example
{

    /**
     *
     * @var Service
     */
    private $id4Me = null;

    /**
     *
     * @var HttpClient
     */
    protected $httpClient = null;

    /**
     *
     * @return \Id4me\RP\Service
     */
    public function getId4Me(): Service
    {
        return $this->id4Me;
    }

    /**
     * Initializes Constructor for Example RP Client Class
     */
    public function __construct()
    {
        $httpClient = new HttpClientGuzzle();
        $this->id4Me = new Service($httpClient);
    }

    /**
     * Main function running Id4Me mechanisms supported by current library
     */
    public function run()
    {
        echo '***********************************ID4me Demo**************************************';
        $identifier = readline('identifier:');
        echo PHP_EOL;
        echo '***********************************Discovery***************************************';
        echo PHP_EOL;
        $authorityName = $this->getId4Me()->discover($identifier);
        var_dump($authorityName);
        echo PHP_EOL;
        echo PHP_EOL;
        echo '***********************************Registration***************************************';
        echo PHP_EOL;
        $openIdConfig = $this->getId4Me()->getOpenIdConfig($authorityName);
        var_dump($openIdConfig);
        echo PHP_EOL;
        $client = $this->getId4Me()->register($openIdConfig, $identifier, 'http://www.rezepte-elster.de/id4me.php');
        var_dump($client);
        echo PHP_EOL;
        echo PHP_EOL;
        echo '***********************************Authenticate***************************************';
        echo PHP_EOL;
        echo "Do following steps:\n";
        echo "1.Please click on login link below\n";
        echo "2.Login with password '123456'\n";
        echo "3.Copy and Paste 'code' value from corresponding url query parameter into code input prompt field below'\n";
        $state = uniqid('oidstate+');
        $authorizationUrl = $this->getId4Me()->getAuthorizationUrl($openIdConfig, $client->getClientId(), $identifier, $client->getActiveRedirectUri(), $state, NULL,
            new ClaimRequestList(
                new ClaimRequest('given_name', TRUE),
                new ClaimRequest('family_name'),
                new ClaimRequest('email', TRUE, 'A valid reason'),
                new ClaimRequest('email_verified', NULL, 'To skip verification')
            )
        );
        var_dump($authorizationUrl);
        echo PHP_EOL;
        echo PHP_EOL;
        $authorizedAccessTokens = $this->getId4Me()->getAuthorizationTokens($openIdConfig, readline('code:'), $client);

        var_dump($authorizedAccessTokens);
        echo PHP_EOL;
        echo PHP_EOL;
        echo '************************************User Info*****************************************';
        $userInfo = $this->getId4Me()->getUserInfo($openIdConfig, $client, $authorizedAccessTokens);
        var_dump($userInfo);
        echo PHP_EOL;
    }
}

$action = new Example();
$action->run();
