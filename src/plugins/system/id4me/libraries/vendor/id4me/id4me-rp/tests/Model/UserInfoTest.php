<?php

/**
 * UserInfo test case.
 */
use Id4me\RP\Model\UserInfo;

class UserInfoTest extends \PHPUnit\Framework\TestCase
{

    /**
     *
     * @var UserInfo
     */
    private $userInfo;

    /**
     *
     * @var UserInfo
     */
    private $emptyUserInfo;
   
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $userInfoArray = json_decode(
            '
                    {
                       "id4me.identifier":"jane.doe.example.com",
                       "iss":"https://acmeauthority.de",
                       "sub":"248289761001",
                       "name":"Jane Doe",
                       "given_name":"Jane",
                       "family_name":"Doe",
                       "middle_name":"Middle",
                       "nickname":"Nickname",
                       "preferred_username":"Preferred",
                       "profile":"Profile",
                       "picture":"http://example.com/janedoe/me.jpg",
                       "website":"http://example.com/",
                       "email":"janedoe@example.com",
                       "email_verified":true,
                       "gender":"male",
                       "birthdate":"2019-01-01",
                       "zoneinfo":"Europe/Paris",
                       "locale":"en-US",
                       "phone_number":"+1 (310) 123-4567",
                       "phone_number_verified":false,
                       "updated_at":1568106443,
                       "address":{
                          "street_address":"1234 Hollywood Blvd.",
                          "locality":"Los Angeles",
                          "region":"CA",
                          "postal_code":"90210",
                          "country":"US"
                       },
                       "custom_claim":"Custom Claim"
                    }
                ',
            TRUE);
            
        $this->userInfo = new UserInfo($userInfoArray);
        $this->emptyUserInfo = new UserInfo(json_decode("{}", TRUE));
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->userInfo = null;
        $this->emptyUserInfo = null;
        
        parent::tearDown();
    }


    /**
     * Tests UserInfo->getIss()
     */
    public function testGetIss()
    {
        $this->assertEquals("https://acmeauthority.de", $this->userInfo->getIss());
        $this->assertNull($this->emptyUserInfo->getIss());
    }

    /**
     * Tests UserInfo->getSub()
     */
    public function testGetSub()
    {
        $this->assertEquals("248289761001", $this->userInfo->getSub());
        $this->assertNull($this->emptyUserInfo->getSub());
    }

    /**
     * Tests UserInfo->getId4meIdentifier()
     */
    public function testGetId4meIdentifier()
    {
        $this->assertEquals("jane.doe.example.com", $this->userInfo->getId4meIdentifier());
        $this->assertNull($this->emptyUserInfo->getId4meIdentifier());
    }

    /**
     * Tests UserInfo->getName()
     */
    public function testGetName()
    {
        $this->assertEquals("Jane Doe", $this->userInfo->getName());
        $this->assertNull($this->emptyUserInfo->getName());
    }

    /**
     * Tests UserInfo->getGivenName()
     */
    public function testGetGivenName()
    {
        $this->assertEquals("Jane", $this->userInfo->getGivenName());
        $this->assertNull($this->emptyUserInfo->getGivenName());
    }

    /**
     * Tests UserInfo->getFamilyName()
     */
    public function testGetFamilyName()
    {
        $this->assertEquals("Doe", $this->userInfo->getFamilyName());
        $this->assertNull($this->emptyUserInfo->getFamilyName());
    }

    /**
     * Tests UserInfo->getMiddleName()
     */
    public function testGetMiddleName()
    {
        $this->assertEquals("Middle", $this->userInfo->getMiddleName());
        $this->assertNull($this->emptyUserInfo->getMiddleName());
    }

    /**
     * Tests UserInfo->getNickname()
     */
    public function testGetNickname()
    {
        $this->assertEquals("Nickname", $this->userInfo->getNickname());
        $this->assertNull($this->emptyUserInfo->getNickname());
    }

    /**
     * Tests UserInfo->getPreferredUsername()
     */
    public function testGetPreferredUsername()
    {
        $this->assertEquals("Preferred", $this->userInfo->getPreferredUsername());
        $this->assertNull($this->emptyUserInfo->getPreferredUsername());
    }

    /**
     * Tests UserInfo->getProfile()
     */
    public function testGetProfile()
    {
        $this->assertEquals("Profile", $this->userInfo->getProfile());
        $this->assertNull($this->emptyUserInfo->getProfile());
    }

    /**
     * Tests UserInfo->getPicture()
     */
    public function testGetPicture()
    {
        $this->assertEquals("http://example.com/janedoe/me.jpg", $this->userInfo->getPicture());
        $this->assertNull($this->emptyUserInfo->getPicture());
    }

    /**
     * Tests UserInfo->getWebsite()
     */
    public function testGetWebsite()
    {
        $this->assertEquals("http://example.com/", $this->userInfo->getWebsite());
        $this->assertNull($this->emptyUserInfo->getWebsite());
    }

    /**
     * Tests UserInfo->getEmail()
     */
    public function testGetEmail()
    {
        $this->assertEquals("janedoe@example.com", $this->userInfo->getEmail());
        $this->assertNull($this->emptyUserInfo->getEmail());
    }

    /**
     * Tests UserInfo->getEmailVerified()
     */
    public function testGetEmailVerified()
    {
        $this->assertEquals(TRUE, $this->userInfo->getEmailVerified());
        $this->assertNull($this->emptyUserInfo->getEmailVerified());
    }

    /**
     * Tests UserInfo->getGender()
     */
    public function testGetGender()
    {
        $this->assertEquals("male", $this->userInfo->getGender());
        $this->assertNull($this->emptyUserInfo->getGender());
    }

    /**
     * Tests UserInfo->getBirthdate()
     */
    public function testGetBirthdate()
    {
        $this->assertEquals("2019-01-01", $this->userInfo->getBirthdate());
        $this->assertNull($this->emptyUserInfo->getBirthdate());
    }

    /**
     * Tests UserInfo->getZoneinfo()
     */
    public function testGetZoneinfo()
    {
        $this->assertEquals("Europe/Paris", $this->userInfo->getZoneinfo());
        $this->assertNull($this->emptyUserInfo->getZoneinfo());
    }

    /**
     * Tests UserInfo->getLocale()
     */
    public function testGetLocale()
    {
        $this->assertEquals("en-US", $this->userInfo->getLocale());
        $this->assertNull($this->emptyUserInfo->getLocale());
    }

    /**
     * Tests UserInfo->getPhoneNumber()
     */
    public function testGetPhoneNumber()
    {
        $this->assertEquals("+1 (310) 123-4567", $this->userInfo->getPhoneNumber());
        $this->assertNull($this->emptyUserInfo->getPhoneNumber());
    }

    /**
     * Tests UserInfo->getPhoneNumberVerified()
     */
    public function testGetPhoneNumberVerified()
    {
        $this->assertEquals(FALSE, $this->userInfo->getPhoneNumberVerified());
        $this->assertNull($this->emptyUserInfo->getPhoneNumberVerified());
    }

    /**
     * Tests UserInfo->getUpdatedAt()
     */
    public function testGetUpdatedAt()
    {
        $this->assertEquals(date_create("2019-09-10T09:07:23+0000"), $this->userInfo->getUpdatedAt());
        $this->assertNull($this->emptyUserInfo->getUpdatedAt());
    }

    /**
     * Tests UserInfo->getClaim()
     */
    public function testGetClaim()
    {
        $this->assertEquals("Custom Claim", $this->userInfo->getClaim("custom_claim"));
        $this->assertNull($this->emptyUserInfo->getClaim("custom_claim"));
    }   
    
    /**
     * Tests UserInfo->getAddress()
     */
    public function testGetAddress()
    {
        $this->assertNotNull($this->userInfo->getAddress());
        $this->assertEquals("US", $this->userInfo->getAddress()->getCountry());
        $this->assertEquals("1234 Hollywood Blvd.", $this->userInfo->getAddress()->getStreetAddress());
        $this->assertEquals("Los Angeles", $this->userInfo->getAddress()->getLocality());
        $this->assertEquals("CA", $this->userInfo->getAddress()->getRegion());
        $this->assertEquals("90210", $this->userInfo->getAddress()->getPostalCode());
        $this->assertNull($this->userInfo->getAddress()->getFormatted());
        $this->assertNull($this->emptyUserInfo->getAddress());
    }
}

