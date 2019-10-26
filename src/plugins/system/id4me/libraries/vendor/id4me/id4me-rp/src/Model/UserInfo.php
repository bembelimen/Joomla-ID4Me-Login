<?php

namespace Id4me\RP\Model;

/**
 * Class UserInfo
 *
 * @package Id4me\RP\Model
 */
class UserInfo extends UserInfoCommon
{


    /**
     * Gets "iss" claim
     * @return string|NULL
     */
    public function getIss()
    {
        return $this->getClaim("iss");
    }


    /**
     * Gets "sub" claim
     * @return string|NULL
     */
    public function getSub()
    {
        return $this->getClaim("sub");
    }

    /**
     * Gets "id4me.identifier" claim
     * @return string|NULL
     */
    public function getId4meIdentifier()
    {
        return $this->getClaim("id4me.identifier");
    }

    /**
     * Gets "name" claim
     * @return string|NULL
     */
    public function getName()
    {
        return $this->getClaim("name");
    }

    /**
     * Gets "given_name" claim
     * @return string|NULL
     */
    public function getGivenName()
    {
        return $this->getClaim("given_name");
    }

    /**
     * Gets "family_name" claim
     * @return string|NULL
     */
    public function getFamilyName()
    {
        return $this->getClaim("family_name");
    }

    /**
     * Gets "middle_name" claim
     * @return string|NULL
     */
    public function getMiddleName()
    {
        return $this->getClaim("middle_name");
    }

    /**
     * Gets "nickname" claim
     * @return string|NULL
     */
    public function getNickname()
    {
        return $this->getClaim("nickname");
    }

    /**
     * Gets "preferred_username" claim
     * @return string|NULL
     */
    public function getPreferredUsername()
    {
        return $this->getClaim("preferred_username");
    }

    /**
     * Gets "profile" claim
     * @return string|NULL
     */
    public function getProfile()
    {
        return $this->getClaim("profile");
    }

    /**
     * Gets "picture" claim
     * @return string|NULL
     */
    public function getPicture()
    {
        return $this->getClaim("picture");
    }

    /**
     * Gets "website" claim
     * @return string|NULL
     */
    public function getWebsite()
    {
        return $this->getClaim("website");
    }

    /**
     * Gets "email" claim
     * @return string|NULL
     */
    public function getEmail()
    {
        return $this->getClaim("email");
    }

    /**
     * Gets "email_verified" claim
     * @return bool|NULL
     */
    public function getEmailVerified()
    {
        return $this->getClaim("email_verified");
    }

    /**
     * Gets "gender" claim
     * @return string|NULL
     */
    public function getGender()
    {
        return $this->getClaim("gender");
    }

    /**
     * Gets "birthdate" claim
     * @return string|NULL
     */
    public function getBirthdate()
    {
        return $this->getClaim("birthdate");
    }

    /**
     * Gets "zoneinfo" claim
     * @return string|NULL
     */
    public function getZoneinfo()
    {
        return $this->getClaim("zoneinfo");
    }

    /**
     * Gets "locale" claim
     * @return string|NULL
     */
    public function getLocale()
    {
        return $this->getClaim("locale");
    }

    /**
     * Gets "phone_number" claim
     * @return string|NULL
     */
    public function getPhoneNumber()
    {
        return $this->getClaim("phone_number");
    }

    /**
     * Gets "phone_number_verified" claim
     * @return bool|NULL
     */
    public function getPhoneNumberVerified()
    {
        return $this->getClaim("phone_number_verified");
    }

    /**
     * Gets "updated_at" claim
     * @return \DateTime|NULL
     */
    public function getUpdatedAt()
    {
        $epoch = $this->getClaim("updated_at");
        return $epoch !== null ? new \DateTime("@$epoch") : null;
    }

    /**
     * Gets "address" claim
     * @return UserInfoAddress|NULL
     */
    public function getAddress()
    {
        if ($this->getClaim("address") !== null) {
            return new UserInfoAddress($this->getClaim("address"));
        } else {
            return null;
        }
    }
}
