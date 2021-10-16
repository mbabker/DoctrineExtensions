<?php

namespace Gedmo\Mapping\Event\Adapter;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interfacing with the MongoDB ODM.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ODM implements AdapterInterface
{
    /**
     * @var EventArgs
     */
    private $args;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * {@inheritdoc}
     */
    public function setEventArgs(EventArgs $args)
    {
        $this->args = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainObjectName()
    {
        return 'Document';
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerName()
    {
        return 'ODM';
    }

    /**
     * {@inheritdoc}
     *
     * @param ClassMetadata $meta
     */
    public function getRootObjectClass($meta)
    {
        return $meta->rootDocumentName;
    }

    /**
     * Set the document manager
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectManager()
    {
        if (!is_null($this->dm)) {
            return $this->dm;
        }

        return $this->__call('getDocumentManager', []);
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function getObjectState($uow, $object)
    {
        return $uow->getDocumentState($object);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        if (is_null($this->args)) {
            throw new RuntimeException('Event args must be set before calling its methods');
        }
        $method = str_replace('Object', $this->getDomainObjectName(), $method);

        return call_user_func_array([$this->args, $method], $args);
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }

    /**
     * {@inheritdoc}
     *
     * @param ClassMetadata $meta
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->identifier;
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork    $uow
     * @param ClassMetadata $meta
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function getScheduledObjectUpdates($uow)
    {
        $updates = $uow->getScheduledDocumentUpdates();
        $upserts = $uow->getScheduledDocumentUpserts();

        return array_merge($updates, $upserts);
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function setOriginalObjectProperty($uow, $oid, $property, $value)
    {
        $uow->setOriginalDocumentProperty($oid, $property, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param UnitOfWork $uow
     */
    public function clearObjectChangeSet($uow, $oid)
    {
        $uow->clearDocumentChangeSet($oid);
    }

    /**
     * Creates an ODM specific LifecycleEventArgs instance.
     *
     * @param object        $object
     * @param ObjectManager $om
     *
     * @return LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($object, $om)
    {
        return new LifecycleEventArgs($object, $om);
    }
}
