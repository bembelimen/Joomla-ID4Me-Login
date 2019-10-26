<?php
namespace Id4me\RP\Model;

use ArrayIterator;
use IteratorAggregate;

/**
 * This is a container for ClaimRequest
 *
 * @package Id4me\RP\Model
 */
class ClaimRequestList implements IteratorAggregate
{

    /**
     *
     * @var ClaimRequest[]
     */
    private $claimRequestList;

    /**
     * ClaimRequestList constructor
     */
    public function __construct(ClaimRequest ...$requestedClaims)
    {
        $this->claimRequestList = $requestedClaims;
    }

    /**
     * gets iterator over claim request list
     *
     * {@inheritDoc}
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->claimRequestList);
    }

    /**
     * gets an array, which may be directly converted to JSON request for OIDC claims
     *
     * @return array
     */
    public function toOIDCAuthorizationRequestArray(): array
    {
        $ret = [];

        foreach ($this->getIterator() as $item) {
            $req = [];
            if ($item->getEssential() !== null) {
                $req['essential'] = $item->getEssential();
            }
            if ($item->getReason() !== null) {
                $req['reason'] = $item->getReason();
            }
            if ($item->getExtraProperties() != null) {
                $req = array_merge($req, $item->getExtraProperties());
            }
            $ret[$item->getName()] = sizeof($req) > 0 ? $req : null;
        }

        return $ret;
    }
}
