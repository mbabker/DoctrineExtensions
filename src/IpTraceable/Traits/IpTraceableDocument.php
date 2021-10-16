<?php

namespace Gedmo\IpTraceable\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Trait integrating common IpTraceable properties for objects with MongoDB ODM annotations.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait IpTraceableDocument
{
    /**
     * @var string
     *
     * @Gedmo\IpTraceable(on="create")
     * @ODM\Field(type="string")
     */
    protected $createdFromIp;

    /**
     * @var string
     *
     * @Gedmo\IpTraceable(on="update")
     * @ODM\Field(type="string")
     */
    protected $updatedFromIp;

    /**
     * Sets the created from IP.
     *
     * @param string $createdFromIp
     *
     * @return $this
     */
    public function setCreatedFromIp($createdFromIp)
    {
        $this->createdFromIp = $createdFromIp;

        return $this;
    }

    /**
     * Returns the created from IP.
     *
     * @return string
     */
    public function getCreatedFromIp()
    {
        return $this->createdFromIp;
    }

    /**
     * Sets the updated from IP.
     *
     * @param string $updatedFromIp
     *
     * @return $this
     */
    public function setUpdatedFromIp($updatedFromIp)
    {
        $this->updatedFromIp = $updatedFromIp;

        return $this;
    }

    /**
     * Returns the updated from IP.
     *
     * @return string
     */
    public function getUpdatedFromIp()
    {
        return $this->updatedFromIp;
    }
}
