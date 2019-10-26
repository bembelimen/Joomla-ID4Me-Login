<?php

use Id4me\RP\Discovery;
use Id4me\RP\Exception\InvalidOpenIdDomainException;
use Id4me\RP\Exception\OpenIdDnsRecordNotFoundException;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for Discovery::getOpenIdDnsRecord()
     *
     * In this case an InvalidOpenIdDomainException is expected
     *
     * @param mixed $identifier
     *
     * @dataProvider getOpenIdDnsRecordDataThrowsInvalidDomainException()
     *
     * @throws InvalidOpenIdDomainException if given consumerId is an invalid domain
     * @throws OpenIdDnsRecordNotFoundException if no matching openId DNS record is found
     */
    public function testGetOpenIdConfigThrowsInvalidOpenIdDomainException(
        $identifier
    ) {
        $this->expectException(InvalidOpenIdDomainException::class);

        $discovery = new Discovery();
        $discovery->getOpenIdDnsRecord($identifier);
    }

    /**
     * Data provider for testing
     *
     * @return array
     */
    public function getOpenIdDnsRecordDataThrowsInvalidDomainException()
    {
        return [
            [null],
            [''],
            [true],
            [false],
            [1],
            [-1],
            ['domain'],
            ['google.com']
        ];
    }

    /**
     * Test for Discovery::getOpenIdDnsRecord()
     *
     * In this case an OpenIdDnsRecordNotFoundException is expected
     */
    public function testGetOpenIdConfigThrowsOpenIdDnsRecordNotFoundException()
    {
        $this->expectException(OpenIdDnsRecordNotFoundException::class);

        $discovery = $this->getDiscoveryForDnsRecordNotFoundException();
        $discovery->getOpenIdDnsRecord('consumerId');
    }

    /**
     * Retrieves a mock instance of Discovery to test case of DNS Record Not Found Exception
     *
     * @return Discovery
     *
     * @throws Exception
     */
    private function getDiscoveryForDnsRecordNotFoundException()
    {
        $discovery = $this->getMockBuilder('Discovery')
            ->disableOriginalConstructor()
            ->setMethods(['getOpenIdDnsRecord'])
            ->getMock();

        $discovery->expects($this->any())
            ->method('getOpenIdDnsRecord')
            ->willThrowException(new OpenIdDnsRecordNotFoundException());


        return $discovery;
    }

    /**
     * Test for Discovery::extractDomainFromIdentifier()
     *
     * @dataProvider getExtractedDomainFromIdentifier
     *
     * @param string $identifier
     * @param string $domain
     */
    public function testExtractDomainFromIdentifier($identifier, $domain)
    {
        $discovery = new Discovery();

        $this->assertSame(
            $discovery->extractDomainFromIdentifierSubdomain($identifier),
            $domain
        );
    }

    /**
     * Data provider for testExtractDomainFromIdentifier()
     *
     * @return array
     */
    public function getExtractedDomainFromIdentifier()
    {
        return [
            ['test.subdomain.domain.org', 'subdomain.domain.org'],
            ['plop.test.subdomain.domain.org', 'test.subdomain.domain.org'],
            ['subdomain.domain.org', 'domain.org'],
            [null, false],
            ['', false],
            ['.', false],
            ['...', false],
            ['domain.com', false],
            ['.domain.com', false]
        ];
    }
}
