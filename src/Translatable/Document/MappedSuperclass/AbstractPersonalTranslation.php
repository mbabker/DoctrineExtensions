<?php

namespace Gedmo\Translatable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * Base Document object for personal translations for the MongoDB ODM.
 *
 * @MongoODM\MappedSuperclass
 */
abstract class AbstractPersonalTranslation
{
    /**
     * @var int
     *
     * @MongoODM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $locale;

    /**
     * Related document with a ManyToOne relation,
     * must be mapped by the user.
     *
     * @var object
     */
    protected $object;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $field;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $content;

    /**
     * Get the document ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the translation locale
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the translation locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the translated field
     *
     * @param string $field
     *
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get the translated field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set the related object
     *
     * @param object $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get the related object
     *
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set the translation content
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the translation content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
