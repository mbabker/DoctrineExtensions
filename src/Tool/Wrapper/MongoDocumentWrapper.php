<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\ODM\MongoDB\DocumentManager;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Wraps a managed document from the MongoDB ODM for more convenient manipulation.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MongoDocumentWrapper extends AbstractWrapper
{
    /**
     * Document identifier
     *
     * @var mixed
     */
    private $identifier;

    /**
     * Internal flag to track if the wrapper is initialized.
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Wraps a document.
     *
     * @param object $document
     */
    public function __construct($document, DocumentManager $dm)
    {
        $this->om = $dm;
        $this->object = $document;
        $this->meta = $dm->getClassMetadata(get_class($this->object));
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
    public function getRootObjectName()
    {
        return $this->meta->rootDocumentName;
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
        return (bool) $this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($single = true)
    {
        if (!$this->identifier) {
            if ($this->object instanceof GhostObjectInterface) {
                $uow = $this->om->getUnitOfWork();
                if ($uow->isInIdentityMap($this->object)) {
                    $this->identifier = (string) $uow->getDocumentIdentifier($this->object);
                } else {
                    $this->initialize();
                }
            }
            if (!$this->identifier) {
                $this->identifier = (string) $this->getPropertyValue($this->meta->identifier);
            }
        }

        return $this->identifier;
    }

    /**
     * Initializes the document if it is a proxy
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof GhostObjectInterface) {
                $uow = $this->om->getUnitOfWork();
                if (!$this->object->isProxyInitialized()) {
                    $persister = $uow->getDocumentPersister($this->meta->name);
                    $identifier = null;
                    if ($uow->isInIdentityMap($this->object)) {
                        $identifier = $this->getIdentifier();
                    } else {
                        // this may not happen but in case
                        $reflProperty = new \ReflectionProperty($this->object, 'identifier');
                        $reflProperty->setAccessible(true);
                        $identifier = $reflProperty->getValue($this->object);
                    }
                    $this->object->__isInitialized__ = true;
                    $persister->load($identifier, $this->object);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEmbeddedAssociation($field)
    {
        return $this->getMetadata()->isSingleValuedEmbed($field);
    }
}
