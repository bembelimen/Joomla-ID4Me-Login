<?php

namespace Id4me\RP\Model;

/**
 * Class IdToken
 *
 * @package Id4me\RP\Model
 */
class IdToken extends JWT
{
    /**
     * IdToken constructor.
     *
     * @param string $idToken
     */
    public function __construct($idToken = null)
    {
        parent::__construct($idToken);
    }

    /**
     * Return Subject (sub)
     *
     * Note: this together with 'iss' is required for identification
     *
     * @return string
     */
    public function getSub(): string
    {
        return $this->getBodyValue("sub");
    }
    
   
    /**
     * get ID4Me identifier
     *
     * Note: this is only a visible name. Use sub/iss combination for identification
     *
     * @return string
     */
    public function getId4meIdentifier(): string
    {
        return $this->getBodyValue("id4me.identifier");
    }
    
   
    /**
     * @return array
     */
    public function getAmr(): array
    {
        return $this->getBodyValue("amr");
    }
}
