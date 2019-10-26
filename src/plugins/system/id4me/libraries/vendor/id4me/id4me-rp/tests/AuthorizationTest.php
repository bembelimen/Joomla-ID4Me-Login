<?php

use Id4me\RP\Authorization;
use Id4me\Test\Mock\HttpClientGuzzle;
use Id4me\RP\Model\OpenIdConfig;
use Id4me\RP\HttpClient;
use Prophecy\Argument;
use Id4me\RP\Model\IdToken;
use Id4me\RP\Validation;
use Id4me\RP\Model\Client;
use Id4me\RP\Model\AuthorizationTokens;
use Id4me\RP\Model\ClaimRequestList;
use Id4me\RP\Model\ClaimRequest;

class AuthorizationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for Authorization::getAuthorizationUrl()
     *
     * @dataProvider getGetAuthorizationUrlData()
     *
     * @param string|NULL $prompt
     * @param array $scopes
     * @param string|NULL $state
     * @param ClaimRequestList|NULL $userinfoclaims
     * @param ClaimRequestList|NULL $idtokenclaims
     * @param string $expectedsuffix
     *
     * @throws ReflectionException
     */
    public function testGetAuthorizationUrl(string $prompt = null, array $scopes, string $state = null,
        ClaimRequestList $userinfoclaims = null, ClaimRequestList $idtokenclaims = null, string $expectedsuffix)
    {
        $authEndpoint = "http://local.host/login";

        $mock = $this->createMock(OpenIdConfig::class);
        $mock->method("getAuthorizationEndpoint")->willReturn($authEndpoint);

        $authorization = new Authorization(new HttpClientGuzzle());

        $clientId    = "clientId";
        $identifier  = "example.org";
        $redirectUri = "http://example.org";

        $expectedUrl = $authEndpoint
                       . "?client_id=" . $clientId
                       . "&login_hint=" . $identifier
                       . "&redirect_uri=" . urlencode($redirectUri)
                       . "&response_type=code"
                       . $expectedsuffix;

        $this->assertEquals(
            $expectedUrl,
            $authorization->getAuthorizationUrl(
                $mock->getAuthorizationEndpoint(),
                $clientId,
                $identifier,
                $redirectUri,
                $state,
                $prompt,
                $userinfoclaims,
                $idtokenclaims,
                $scopes
            )
        );
    }

    /**
     * @return array
     */
    public function getGetAuthorizationUrlData()
    {
        $ret =  [
            ['login', [], 'test', NULL, NULL,
                '&scope=openid&state=test&prompt=login'],
            ['login', ['openid'], 'test2', NULL, NULL,
                '&scope=openid&state=test2&prompt=login'],
            ['none', ['openid', 'profile'], 'test@/%', NULL, NULL, '&scope=openid%20profile&state=test%40%2F%25&prompt=none'],
            [NULL, [], NULL,
                new ClaimRequestList(
                    new ClaimRequest('given_name'),
                    new ClaimRequest('family_name')
                ), NULL,
                '&scope=openid'
                . '&claims=%7B%22userinfo%22%3A%7B%22given_name%22%3Anull%2C%22family_name%22%3Anull%7D%7D'],
            [NULL, [], NULL,
                new ClaimRequestList(
                    new ClaimRequest('given_name', true),
                    new ClaimRequest('email', NULL, 'A valid reason')
                ), NULL,
                '&scope=openid'
                . '&claims=%7B%22userinfo%22%3A%7B%22given_name%22%3A%7B%22essential%22%3Atrue%7D%2C%22email%22%3A%7B%22reason%22%3A%22A%20valid%20reason%22%7D%7D%7D'],
            [NULL, [], NULL,
                NULL,
                new ClaimRequestList(
                    new ClaimRequest('given_name', true),
                    new ClaimRequest('email', NULL, 'A valid reason')
                    ),
                '&scope=openid'
                . '&claims=%7B%22id_token%22%3A%7B%22given_name%22%3A%7B%22essential%22%3Atrue%7D%2C%22email%22%3A%7B%22reason%22%3A%22A%20valid%20reason%22%7D%7D%7D'],
        ];
        return $ret;
    }

    /**
     * Testing authorize method
     */
    public function testAuthorize()
    {
        $mockTokenEndpoint = 'https://acme.com/token';
        $mockJwksUri = 'https://acme.com/jwks.json';
        $mockIssuer = 'https://id.test.denic.de';
        $mockIdToken = 'eyJraWQiOiJGT3Z5IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiIrOW4xY1J1akxWVXcwbGU3SzRMdGw0bjBZNDVHalpuWWtHRkpPbERhdDBBQ1pFMXNHOVp6TlV2QWRmK2t1dGx0IiwiYXVkIjoiN2Z0ajZ2dzU0NXN1ayIsImlkNG1lLmlkZW50aWZpZXIiOiJzbWlsZXkubGFuZCIsImFtciI6WyJwd2QiXSwiaXNzIjoiaHR0cHM6XC9cL2lkLnRlc3QuZGVuaWMuZGUiLCJleHAiOjE1NTYxMTY5OTYsImlhdCI6MTU1NjExNjA5Nn0.DkrevqYO-MFCZh38HF9Hs4uRn37sxG4IjvY0XYihQq72iaWoLVz5VHt6-uxWXJ3WQYiZDDOTm55hvDr37iO9jNIVUBV0mmnF5RAHZx7tllgTWzFek2TPCLu9OItiKJJx-ByqKm-Zm-NZvrDbj90xtZEnVZLk8mrPHRAoc8KvTmZ69iCGlb-2Rpood1vIqakDbz2MjBnypcI_Sh_xmISfdK-5r7SK-HUxeSMFOnYEp5Ou1IRaTk2n_z0usDX-Do0yPGNl5MMfOlB4wHayuUP8i0-zvOvqf0mGXc-_xyDvoUly-hDZ-XMmVE_iV-PdNXsrkV90SW5O27M6c4rJLNNw3g';
        $mockAccessToken = uniqid('acctoken_');

        $code = uniqid('code_');
        $redirectUrl = 'https://acmepr.com/callback';
        $clientId = '7ftj6vw545suk';
        $clientSecret = 'clientsec_';


        $openidconfigprop = $this->prophesize(OpenIdConfig::class);
        $openidconfigprop->getTokenEndpoint()->willReturn($mockTokenEndpoint);
        $openidconfigprop->getJwksUri()->willReturn($mockJwksUri);
        $openidconfigprop->getIssuer()->willReturn($mockIssuer);

        $httpclientprop = $this->prophesize(HttpClient::class);
        // mocking jwks call
        $httpclientprop
        ->get($mockJwksUri)
        ->willReturn(file_get_contents(sprintf('%s/mocks/jwks.json', __DIR__)));

        // mocking token call
        $httpclientprop
        ->post($mockTokenEndpoint, Argument::type('string'), Argument::type('array'))
        ->willReturn(json_encode(
                [
                    'id_token' => $mockIdToken,
                    'access_token' => $mockAccessToken
                ]));

        Validation::freezeTime(1556116196);

        $authorization = new Authorization($httpclientprop->reveal());

        $ret = $authorization->authorize(
            $openidconfigprop->reveal(),
            $code,
            $redirectUrl,
            $clientId,
            $clientSecret);

        $httpclientprop
        ->post(
            $mockTokenEndpoint,
            'code=' . $code .'&grant_type=authorization_code&redirect_uri=https%3A%2F%2Facmepr.com%2Fcallback',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic N2Z0ajZ2dzU0NXN1azpjbGllbnRzZWNf'
            ])
            ->shouldHaveBeenCalled();

        $this->assertArrayHasKey('identifier', $ret);
        $this->assertEquals('smiley.land', $ret['identifier']);
        $this->assertArrayHasKey('iss', $ret);
        $this->assertEquals($mockIssuer, $ret['iss']);
        $this->assertArrayHasKey('sub', $ret);
        $this->assertEquals('+9n1cRujLVUw0le7K4Ltl4n0Y45GjZnYkGFJOlDat0ACZE1sG9ZzNUvAdf+kutlt', $ret['sub']);
    }

    /**
     * Testing getAuthorizationTokens method
     */
    public function testGetAuthorizationTokens()
    {
        $mockTokenEndpoint = 'https://acme.com/token';
        $mockJwksUri = 'https://acme.com/jwks.json';
        $mockIssuer = 'https://id.test.denic.de';
        $mockIdToken = 'eyJraWQiOiJGT3Z5IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiIrOW4xY1J1akxWVXcwbGU3SzRMdGw0bjBZNDVHalpuWWtHRkpPbERhdDBBQ1pFMXNHOVp6TlV2QWRmK2t1dGx0IiwiYXVkIjoiN2Z0ajZ2dzU0NXN1ayIsImlkNG1lLmlkZW50aWZpZXIiOiJzbWlsZXkubGFuZCIsImFtciI6WyJwd2QiXSwiaXNzIjoiaHR0cHM6XC9cL2lkLnRlc3QuZGVuaWMuZGUiLCJleHAiOjE1NTYxMTY5OTYsImlhdCI6MTU1NjExNjA5Nn0.DkrevqYO-MFCZh38HF9Hs4uRn37sxG4IjvY0XYihQq72iaWoLVz5VHt6-uxWXJ3WQYiZDDOTm55hvDr37iO9jNIVUBV0mmnF5RAHZx7tllgTWzFek2TPCLu9OItiKJJx-ByqKm-Zm-NZvrDbj90xtZEnVZLk8mrPHRAoc8KvTmZ69iCGlb-2Rpood1vIqakDbz2MjBnypcI_Sh_xmISfdK-5r7SK-HUxeSMFOnYEp5Ou1IRaTk2n_z0usDX-Do0yPGNl5MMfOlB4wHayuUP8i0-zvOvqf0mGXc-_xyDvoUly-hDZ-XMmVE_iV-PdNXsrkV90SW5O27M6c4rJLNNw3g';
        $mockAccessToken = uniqid('acctoken_');

        $code = uniqid('code_');
        $redirectUrl = 'https://acmepr.com/callback';
        $clientId = '7ftj6vw545suk';
        $clientSecret = 'clientsec_';
        $mockClient = new Client($mockIssuer);
        $mockClient->setClientId($clientId);
        $mockClient->setClientSecret($clientSecret);
        $mockClient->setActiveRedirectUri($redirectUrl);


        $openidconfigprop = $this->prophesize(OpenIdConfig::class);
        $openidconfigprop->getTokenEndpoint()->willReturn($mockTokenEndpoint);
        $openidconfigprop->getJwksUri()->willReturn($mockJwksUri);
        $openidconfigprop->getIssuer()->willReturn($mockIssuer);

        $httpclientprop = $this->prophesize(HttpClient::class);
        // mocking jwks call
        $httpclientprop
        ->get($mockJwksUri)
        ->willReturn(file_get_contents(sprintf('%s/mocks/jwks.json', __DIR__)));

        // mocking token call
        $httpclientprop
        ->post($mockTokenEndpoint, Argument::type('string'), Argument::type('array'))
        ->willReturn(json_encode(
            [
                'id_token' => $mockIdToken,
                'access_token' => $mockAccessToken
            ]));

        Validation::freezeTime(1556116196);

        $authorization = new Authorization($httpclientprop->reveal());

        $ret = $authorization->getAuthorizationTokens(
            $openidconfigprop->reveal(),
            $code,
            $mockClient);

        $httpclientprop
        ->post(
            $mockTokenEndpoint,
            'code=' . $code .'&grant_type=authorization_code&redirect_uri=https%3A%2F%2Facmepr.com%2Fcallback',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic N2Z0ajZ2dzU0NXN1azpjbGllbnRzZWNf'
            ])
            ->shouldHaveBeenCalled();

        $this->assertEquals('smiley.land', $ret->getIdTokenDecoded()->getId4meIdentifier());
        $this->assertEquals($mockIssuer, $ret->getIdTokenDecoded()->getIss());
        $this->assertNotNull($ret->getIdTokenDecoded());
        $this->assertInstanceOf(IdToken::class, $ret->getIdTokenDecoded());
        $this->assertEquals(
            '+9n1cRujLVUw0le7K4Ltl4n0Y45GjZnYkGFJOlDat0ACZE1sG9ZzNUvAdf+kutlt',
            $ret->getIdTokenDecoded()->getSub()
        );
        $this->assertEquals(['pwd'], $ret->getIdTokenDecoded()->getAmr());
        $this->assertEquals($mockAccessToken, $ret->getAccessToken());
    }


    /**
     * Testing getUserInfo method
     */
    public function testGetUserInfo()
    {
        $mockJwksUri = 'https://acme.com/jwks.json';
        $mockIssuer = 'https://id.test.denic.de';
        $mockAccessToken = 'eyJraWQiOiJGT3Z5IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiI3a242RU55YUxTVCtJS3BxdGhYU0pxMjE3cXlNVTR5OFB3Q1NlTnN4UCs5TDV4a2NOTUtFSFM0RHNFXC93K25YUSIsImlkNG1lLmlkZW50aWZpZXIiOiJpZHRlc3QxLmRvbWFpbmlkLmNvbW11bml0eSIsImNsbSI6WyJlbWFpbF92ZXJpZmllZCIsImdpdmVuX25hbWUiLCJmYW1pbHlfbmFtZSIsImVtYWlsIl0sInNjb3BlIjpbIm9wZW5pZCJdLCJpc3MiOiJodHRwczpcL1wvaWQudGVzdC5kZW5pYy5kZSIsImV4cCI6MTU2NjI0ODAxOSwiaWF0IjoxNTY2MjQ3NDE5LCJqdGkiOiJNdWRDdDJYMmdhOCIsImNsaWVudF9pZCI6InVtN3d6bG9iNnQ3aDYifQ.OyK-wRT0PYgTb5nIOtxaJi0NxvAhsykEvClHATnT3uxL0DzTUasAKHwjKJ-ziSTaW36WfxOcJxq6eDXLczI38oOyiRoHj-HNFTRISw0tPLUL2uyE8fREISLEztHZXLQdlLlfcN5XzLd_fQZfPX_e4hKu_xSrYCSemN-OksE-CYPQLwQ0FjX_pcczU593CoPvdm_RkyldESt796N9dY1eLG7z4fq3pd5ryYbf7q4cFblhtdHeM2FfWZiTB3AiHZFIjtB9jfExOAR-ZsPwWubLzYb1iTRY6JQP-IIWsu3Swxr0xcrxnjGpZdNa8J21xynxfc4Bc-7uAC0oJnMeHKJJIg';
        $mockAuthorizationTokens = new AuthorizationTokens(
            [
                'access_token' => $mockAccessToken
            ]);
        $mockAuthorityUserInfoURL = 'https://id.denic.de/userinfo';
        $mockAgentOpenIdConfigURL = 'https://identityagent.de/.well-known/openid-configuration';
        $mockAgentJwksURL = 'https://identityagent.de/jwks.json';
        $mockAgentUserInfoURL = 'https://identityagent.de/userinfo';
        $mockClientID = 'bpwlftz52dw4u';
        $userInfoFromAuthority = 'eyJraWQiOiJGT3Z5IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiI3a242RU55YUxTVCtJS3BxdGhYU0pxMjE3cXlNVTR5OFB3Q1NlTnN4UCs5TDV4a2NOTUtFSFM0RHNFXC93K25YUSIsImF1ZCI6ImJwd2xmdHo1MmR3NHUiLCJfY2xhaW1fbmFtZXMiOnsiYWRkcmVzcyI6IjBhODg3YjBhLTIzZWQtNGE2ZC1hNzBmLTA5MDE5OWQwZTc3YiIsImJpcnRoZGF0ZSI6IjBhODg3YjBhLTIzZWQtNGE2ZC1hNzBmLTA5MDE5OWQwZTc3YiIsIm5hbWUiOiIwYTg4N2IwYS0yM2VkLTRhNmQtYTcwZi0wOTAxOTlkMGU3N2IiLCJlbWFpbCI6IjBhODg3YjBhLTIzZWQtNGE2ZC1hNzBmLTA5MDE5OWQwZTc3YiJ9LCJfY2xhaW1fc291cmNlcyI6eyIwYTg4N2IwYS0yM2VkLTRhNmQtYTcwZi0wOTAxOTlkMGU3N2IiOnsiYWNjZXNzX3Rva2VuIjoiZXlKcmFXUWlPaUpHVDNaNUlpd2lZV3huSWpvaVVsTXlOVFlpZlEuZXlKemRXSWlPaUkzYTI0MlJVNTVZVXhUVkN0SlMzQnhkR2hZVTBweE1qRTNjWGxOVlRSNU9GQjNRMU5sVG5ONFVDczVURFY0YTJOT1RVdEZTRk0wUkhORlhDOTNLMjVZVVNJc0ltbGtORzFsTG1sa1pXNTBhV1pwWlhJaU9pSnBaSFJsYzNReExtUnZiV0ZwYm1sa0xtTnZiVzExYm1sMGVTSXNJbWxrWlc1MGFXWnBaWElpT2lKcFpIUmxjM1F4TG1SdmJXRnBibWxrTG1OdmJXMTFibWwwZVNJc0ltbGtORzFsSWpvaWFXUjBaWE4wTVM1a2IyMWhhVzVwWkM1amIyMXRkVzVwZEhraUxDSmpiRzBpT2xzaVlXUmtjbVZ6Y3lJc0ltSnBjblJvWkdGMFpTSXNJbTVoYldVaUxDSmxiV0ZwYkNKZExDSnpZMjl3WlNJNld5SnZjR1Z1YVdRaVhTd2lhWE56SWpvaWFIUjBjSE02WEM5Y0wybGtMblJsYzNRdVpHVnVhV011WkdVaUxDSmxlSEFpT2pFMU5UZzVPVEl5T1RVc0ltbGhkQ0k2TVRVMU9EazVNVFk1TlN3aWFuUnBJam9pUzFST1gyOVJXVmQyVjBVaUxDSmpiR2xsYm5SZmFXUWlPaUppY0hkc1puUjZOVEprZHpSMUluMC5pdGRLYlc4QkptUVU3ZkVFV3hXM2tnRTVJSFFlYl83UFVjUWhwLVBJTGdxV0Q1eG5OTDgxWktqVThRZnpqczVYVk5KWVRiQlNoQXFBbWlRa3VrQ1ZWMGttV3lZV0xMNXdZYkw3VnN0ODRoOW9vZzBHZGx0LVJYREVHRGdJZUtRNHVWTXZ5a29ZZGJqMDZydmY3dTdoNENVc25tcHVKVU9HMzBkNVpxMG1YNEZhRFUwMVp0eFFNaFVIcGlkbTA3VFRvZGJLb0FrMkJBRHFqRFpwcmNfRGhsMDBiT2FSODJpOFlLWlpZVDB4X2podU1BUGM5cFhJUy1LZnFJTVdkem15S3d1SVVpVmVfMThHVjFhMHE5Y1pUaXU4eF94c2dvM1VHdWhHREo2QlZMNEpBZXFNWWZ0M1cwUUpvMkIxLW5CdzF1QU8xZjZTTXZ3Ql9ncUtYYzZSSWciLCJlbmRwb2ludCI6Imh0dHBzOlwvXC9pZGVudGl0eWFnZW50LmRlXC91c2VyaW5mbyJ9fSwiaXNzIjoiaHR0cHM6XC9cL2lkLnRlc3QuZGVuaWMuZGUiLCJpYXQiOjE1NTg5OTE3NzJ9.Ui1msk7Sy3l2FRZjsBIaHFeJKJYFnRESgyiW3kadv_vnhWYMAt1LtulKn-PaOndfXQbvrA-mTDb_-cvaT8V91BblSndaPt8b1DN-yRPpsHBevGHsmuJ1IB2zKI6z_7YupH9BdHQh133w7M--hSExWVw18fBnpwCApAm21GfAlDeS9tYstpgUFNRqlOxdi0_rwDToztucdDWImy5VvP2ZJZ_ShISjnlbAz7UBgWmS-Qlq48a1xJZhc6wNBROU66ITmFOseDvu-YJKEuJhVRwlLwb6PfQiZdpWOGbpvOAcYhJo-_Wa1IKXS9xx8zZQK0RJwY9IgtHNXutmGfC1w1JeGA';
        $userInfoFromAgent = 'eyJ0eXAiOiJKV1QiLCJraWQiOiJrZXlJZCIsImFsZyI6IlJTMjU2In0.eyJpZDRtZS5pZGVudGl0eSI6ImlkdGVzdDEuZG9tYWluaWQuY29tbXVuaXR5IiwiZW1haWwiOiJpZHRlc3QxQGRvbWFpbmlkLmNvbW11bml0eSIsImV4cCI6MTU1ODk5MjIzNywiYWRkcmVzcyI6eyJyZWdpb24iOiIiLCJjb3VudHJ5IjoiTmV2ZXJsYW5kIiwic3RyZWV0X2FkZHJlc3MiOiIiLCJmb3JtYXR0ZWQiOiIiLCJwb3N0YWxfY29kZSI6IiIsImxvY2FsaXR5IjoiIn0sIm5iZiI6MTU1ODk5MTkzNywic3ViIjoiN2tuNkVOeWFMU1QrSUtwcXRoWFNKcTIxN3F5TVU0eThQd0NTZU5zeFArOUw1eGtjTk1LRUhTNERzRS93K25YUSIsIm5hbWUiOiJUZXN0IFVzZXIgMSIsImJpcnRoZGF0ZSI6IjIwMTktMDMtMTEiLCJhdWQiOiJicHdsZnR6NTJkdzR1IiwiaWF0IjoxNTU4OTkxOTM3LCJ1cGRhdGVkX2F0IjoxNTUzNDQ2NTEyLCJpc3MiOiJodHRwczovL2lkZW50aXR5YWdlbnQuZGUiLCJpZDRtZS5pZGVudGlmaWVyIjoiaWR0ZXN0MS5kb21haW5pZC5jb21tdW5pdHkifQ.Pl4-gLFqkTeJUZe3hR8CSm2Et-OWTYKYJy4416z1U482DTWCN9QAXbRt1zB92I5bm2R_pDwOe73_cb69lsuG14JfazHXJvutQ_j8mlUngyJ4Cgye_iploy1SfeBYr31D8WUugamlRfe0Q-dx7cC68P58FFQ8TItlnMP8ALuN-R_-cBUns09Ld88QeupEUoWaYqTKw_m1w67kP858IXuCRvRfGGymKWOACWDn-yBgLFzyXA3-vmc9-BtzDlJeJ75gPltoYqlEC0RhHX3AfTtZwT7hmAR7LNocESCY4fv5KWJluudVKogEYTmeZjM9s_-1K703Q1DNsqYJqRE2xlczjA';

        $openidconfigprop = $this->prophesize(OpenIdConfig::class);
        $openidconfigprop->getUserInfoEndpoint()->willReturn($mockAuthorityUserInfoURL);
        $openidconfigprop->getJwksUri()->willReturn($mockJwksUri);
        $openidconfigprop->getIssuer()->willReturn($mockIssuer);

        $client = new Client($mockIssuer);
        $client->setUserInfoSignedResponseAlg('RS256');
        $client->setClientId($mockClientID);

        $httpclientprop = $this->prophesize(HttpClient::class);
        // mocking jwks call to Authority
        $httpclientprop
        ->get($mockJwksUri)
        ->willReturn(file_get_contents(sprintf('%s/mocks/jwks.json', __DIR__)));

        // mocking OpenID config call to Agent
        $httpclientprop
        ->get($mockAgentOpenIdConfigURL, Argument::type('array'))
        ->willReturn(file_get_contents(sprintf('%s/mocks/openIdConfigAgent.json', __DIR__)));

        // mocking jwks call to Agent
        $httpclientprop
        ->get($mockAgentJwksURL)
        ->willReturn(file_get_contents(sprintf('%s/mocks/jwksagent.json', __DIR__)));

        // mocking userinfo call to Authority
        $httpclientprop
        ->get(
            $mockAuthorityUserInfoURL,
            [
                'Authorization' => 'Bearer ' . $mockAccessToken,
                'Accept' => 'application/jwt'
            ])
        ->willReturn($userInfoFromAuthority)
        ->shouldBeCalledTimes(2);

        // mocking userinfo call to Agent
        $httpclientprop
        ->get($mockAgentUserInfoURL, Argument::type('array'))
        ->willReturn($userInfoFromAgent);

        Validation::freezeTime(1556116196);

        $authorization = new Authorization($httpclientprop->reveal());

        // a test without recoursion (depth == 0)
        $ret = $authorization->getUserInfo(
            $openidconfigprop->reveal(),
            $client,
            $mockAuthorizationTokens,
            0);

        $this->assertEquals($mockIssuer, $ret->getIss());
        $this->assertEquals('7kn6ENyaLST+IKpqthXSJq217qyMU4y8PwCSeNsxP+9L5xkcNMKEHS4DsE/w+nXQ', $ret->getSub());
        $this->assertNull($ret->getName());

        // a test with recoursion (depth != 0)
        $authorization2 = new Authorization($httpclientprop->reveal());
        $ret = $authorization2->getUserInfo(
            $openidconfigprop->reveal(),
            $client,
            $mockAuthorizationTokens,
            1);

        $this->assertEquals($mockIssuer, $ret->getIss());
        $this->assertEquals('7kn6ENyaLST+IKpqthXSJq217qyMU4y8PwCSeNsxP+9L5xkcNMKEHS4DsE/w+nXQ', $ret->getSub());
        $this->assertEquals('Test User 1', $ret->getName());
        $this->assertEquals('2019-03-11', $ret->getBirthdate());
        $this->assertEquals('idtest1@domainid.community', $ret->getEmail());
    }
}
