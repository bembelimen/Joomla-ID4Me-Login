<?php

namespace Id4me\RP\Model;

/**
 * UserInfoAddress class
 *
 * @package Id4me\RP\Model
 */
class UserInfoAddress extends UserInfoCommon
{
    /**
     * Gets "formatted" claim
     * @return string|NULL
     */
    public function getFormatted()
    {
        return $this->getClaim("formatted");
    }

    /**
     * Gets "street_address" claim
     * @return string|NULL
     */
    public function getStreetAddress()
    {
        return $this->getClaim("street_address");
    }

    /**
     * Gets "locality" claim
     * @return string|NULL
     */
    public function getLocality()
    {
        return $this->getClaim("locality");
    }

    /**
     * Gets "region" claim
     * @return string|NULL
     */
    public function getRegion()
    {
        return $this->getClaim("region");
    }

    /**
     * Gets "postal_code" claim
     * @return string|NULL
     */
    public function getPostalCode()
    {
        return $this->getClaim("postal_code");
    }

    /**
     * Gets "country" claim
     * @return string|NULL
     */
    public function getCountry()
    {
        return $this->getClaim("country");
    }
}
