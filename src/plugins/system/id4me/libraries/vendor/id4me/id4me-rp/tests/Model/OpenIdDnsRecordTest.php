<?php

class OpenIdDnsRecordTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Check that the first valid dns txt record is used
     */
    public function testInit()
    {
        $expectedIss   = "id.denic.de";
        $dnsTxtEntries = [
            "v=Blub;iss=id.denic.de;clp=identityagent.de",
            "v=OID1;iss=$expectedIss;clp=identityagent.de",
            "v=HickHack;iss=id.denic.de;clp=identityagent.de",
            "v=OID1;iss=id.local.host;clp=identity.local",
        ];

        $entry = new \Id4me\RP\Model\OpenIdDnsRecord($dnsTxtEntries);

        $this->assertEquals($entry->getIdentityAuthority(), $expectedIss);
    }

    /**
     * Check that the values are empty if no valid entry was found
     */
    public function testInitInvalid()
    {
        $dnsTxtEntries = [
            "v=Blub;iss=id.denic.de;clp=identityagent.de",
            "v=HickHack;iss=id.denic.de;clp=identityagent.de",
            "v=OID2;iss=id.local.host;clp=identity.local",
        ];

        $entry = new \Id4me\RP\Model\OpenIdDnsRecord($dnsTxtEntries);

        $this->assertEmpty($entry->getIdentityAuthority());
    }

    /**
     * Check that the values are empty if no valid response
     */
    public function testInitEmpty()
    {
        $dnsTxtEntries = [
        ];
        
        $entry = new \Id4me\RP\Model\OpenIdDnsRecord($dnsTxtEntries);
        
        $this->assertEmpty($entry->getIdentityAuthority());
    }
}
