<?php

namespace Gedmo\Translatable\Entity\MappedSuperclass;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base Entity object for personal translations for the ORM.
 *
 * @ORM\MappedSuperclass
 */
#[ORM\MappedSuperclass]
abstract class AbstractPersonalTranslation
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=8)
     */
    #[ORM\Column(type: Types::STRING, length: 8)]
    protected $locale;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    #[ORM\Column(type: Types::STRING, length: 32)]
    protected $field;

    /**
     * Related entity with a ManyToOne relation,
     * must be mapped by the user.
     *
     * @var object
     */
    protected $object;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $content;

    /**
     * Get the entity ID.
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
