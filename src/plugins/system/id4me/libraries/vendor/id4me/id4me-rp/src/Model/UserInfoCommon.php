<?php

namespace Id4me\RP\Model;

/**
 * Class UserInfoCommon
 *
 * @package Id4me\RP\Model
 */
class UserInfoCommon
{
    /**
     *
     * @var array
     */
    private $userInfoArray;

    /**
     * UserInfoCommon constructor
     *
     * @param array $userInfoContent array with content of userinfo JSON
     */
    public function __construct(array $userInfoContent)
    {
        $this->userInfoArray = $userInfoContent;
    }

    /**
     * Gets any user info claim
     * @param string $name
     * @return mixed|NULL
     */
    public function getClaim(string $name)
    {
        if (array_key_exists($name, $this->userInfoArray)) {
            return $this->userInfoArray[$name];
        } else {
            return null;
        }
    }
}
