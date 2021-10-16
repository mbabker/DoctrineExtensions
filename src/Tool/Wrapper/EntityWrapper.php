<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Wraps a managed entity from the ORM for more convenient manipulation.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityWrapper extends AbstractWrapper
{
    /**
     * Entity identifier
     *
     * @var array
     */
    private $identifier;

    /**
     * Internal flag to track if the wrapper is initialized.
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Wraps an entity.
     *
     * @param object $entity
     */
    public function __construct($entity, EntityManagerInterface $em)
    {
        $this->om = $em;
        $this->object = $entity;
        $this->meta = $em->getClassMetadata(get_class($this->object));
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValue($property)
    {
        $this->initialize();

        return $this->meta->getReflectionProperty($property)->getValue($this->object);
    }

    /**
     * {@inheritdoc}
     */
    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->object, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasValidIdentifier()
    {
        return null !== $this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootObjectName()
    {
        return $this->meta->rootEntityName;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($single = true)
    {
        if (null === $this->identifier) {
            if ($this->object instanceof Proxy) {
                $uow = $this->om->getUnitOfWork();
                if ($uow->isInIdentityMap($this->object)) {
                    $this->identifier = $uow->getEntityIdentifier($this->object);
                } else {
                    $this->initialize();
                }
            }
            if (null === $this->identifier) {
                $this->identifier = [];
                $incomplete = false;
                foreach ($this->meta->identifier as $name) {
                    $this->identifier[$name] = $this->getPropertyValue($name);
                    if (null === $this->identifier[$name]) {
                        $incomplete = true;
                    }
                }
                if ($incomplete) {
                    $this->identifier = null;
                }
            }
        }
        if ($single && is_array($this->identifier)) {
            return reset($this->identifier);
        }

        return $this->identifier;
    }

    /**
     * Initializes the entity if it is a proxy
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof Proxy) {
                if (!$this->object->__isInitialized__) {
                    $this->object->__load();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEmbeddedAssociation($field)
    {
        return false;
    }
}
