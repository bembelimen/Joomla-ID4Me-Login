<?php
namespace Id4me\RP\Model;

/**
 * Class representing a single claim request and it's properties
 *
 * @package Id4me\RP\Model
 */
class ClaimRequest
{

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var bool
     */
    private $essential;

    /**
     *
     * @var string
     */
    private $reason;

    /**
     *
     * @var array
     */
    private $extraProperties;

    /**
     * Constructs ClaimRequest
     *
     * @param string $name
     * @param bool|NULL $essential
     * @param string|NULL $reason
     * @param array $extraProperties
     */
    public function __construct(
        string $name,
        bool $essential = null,
        string $reason = null,
        array $extraProperties = []
    ) {
        $this->name = $name;
        $this->essential = $essential;
        $this->reason = $reason;
        $this->extraProperties = $extraProperties;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @return boolean|NULL
     */
    public function getEssential()
    {
        return $this->essential;
    }

    /**
     *
     * @param boolean|NULL $essential
     */
    public function setEssential(bool $essential = null)
    {
        $this->essential = $essential;
    }

    /**
     *
     * @return ?string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     *
     * @param string|NULL $reason
     */
    public function setReason(string $reason = null)
    {
        $this->reason = $reason;
    }

    /**
     *
     * @return array
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    /**
     *
     * @param array $extraProperties
     */
    public function setExtraProperties($extraProperties)
    {
        $this->extraProperties = $extraProperties;
    }
}
