<?php

namespace Gedmo\Blameable\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Trait integrating common Blameable properties for objects with MongoDB ODM annotations.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait BlameableDocument
{
    /**
     * @var string
     *
     * @Gedmo\Blameable(on="create")
     * @ODM\Field(type="string")
     */
    protected $createdBy;

    /**
     * @var string
     *
     * @Gedmo\Blameable(on="update")
     * @ODM\Field(type="string")
     */
    protected $updatedBy;

    /**
     * Sets the created by information.
     *
     * @param string $createdBy
     *
     * @return $this
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Returns the created by information.
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Sets the updated by information.
     *
     * @param string $updatedBy
     *
     * @return $this
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Returns the updated by information.
     *
     * @return string
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
