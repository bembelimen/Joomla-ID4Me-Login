<?php

namespace Id4me\RP\Model;

use Id4me\RP\Helper\Base64UrlHelper;

/**
 * Class JWT
 *
 * @package Id4me\RP\Model
 */
class JWT
{
    /**
     * @var string
     */
    protected $headerOriginal;

    /**
     * @var array
     */
    protected $headerDecoded;

    /**
     * @var string
     */
    protected $bodyOriginal;

    /**
     * @var array
     */
    protected $bodyDecoded;

    /**
     * @var string
     */
    protected $signatureOriginal;

    /**
     * @var string
     */
    protected $signatureDecoded;

    /**
     * JWT constructor.
     *
     * @param string $token
     */
    public function __construct($token = null)
    {
        if (! is_null($token)) {
            $idTokenParts = explode('.', $token);

            if (count($idTokenParts) == 3) {
                list(
                    $this->headerOriginal,
                    $this->bodyOriginal,
                    $this->signatureOriginal
                ) = $idTokenParts;

                // Decode token parts
                $this->headerDecoded = json_decode(Base64UrlHelper::base64urlDecode($this->headerOriginal), true);
                $this->bodyDecoded = json_decode(Base64UrlHelper::base64urlDecode($this->bodyOriginal), true);
                $this->signatureDecoded = Base64UrlHelper::base64urlDecode($this->signatureOriginal);
            }
        }
    }
    
    /**
     * return the Issuer (iss)
     *
     * Note: this together with 'iss' is required for identification
     *
     * @return string
     */
    public function getIss(): string
    {
        return $this->getBodyValue("iss");
    }

    /**
     * @return string
     */
    public function getOriginalHeader()
    {
        return $this->headerOriginal;
    }

    /**
     * @return array
     */
    public function getDecodedHeader()
    {
        return $this->headerDecoded;
    }

    /**
     * @return string
     */
    public function getOriginalBody()
    {
        return $this->bodyOriginal;
    }

    /**
     * @return array
     */
    public function getDecodedBody()
    {
        return $this->bodyDecoded;
    }

    /**
     * @return string
     */
    public function getOriginalSignature()
    {
        return $this->signatureOriginal;
    }

    /**
     * @return string
     */
    public function getDecodedSignature()
    {
        return $this->signatureDecoded;
    }

    /**
     * @param string $property
     *
     * @return string
     */
    public function getHeaderValue($property)
    {
        return $this->fetchArrayPropertyValue($this->headerDecoded, $property);
    }

    /**
     * @param string $property
     *
     * @return string
     */
    public function getBodyValue($property)
    {
        return $this->fetchArrayPropertyValue($this->bodyDecoded, $property);
    }


    /**
     * Extracts token property value out of given token data list if found
     *
     * @param array  $source
     * @param string $property
     *
     * @return mixed
     */
    private function fetchArrayPropertyValue(array $source, string $property)
    {
        $result = null;
        
        if (array_key_exists($property, $source)) {
            $result = $source[$property];
        }

        return $result;
    }
}
