<?php

namespace Gedmo\Mapping\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/**
 * Doctrine event adapter for Doctrine extensions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface AdapterInterface
{
    /**
     * Set the event args object.
     */
    public function setEventArgs(EventArgs $args);

    /**
     * Calls a method on the event args object.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args);

    /**
     * Get the name of the domain object.
     *
     * @return string
     */
    public function getDomainObjectName();

    /**
     * Get the name of the manager used by this adapter.
     *
     * @return string
     */
    public function getManagerName();

    /**
     * Get the root object class, handles inheritance.
     *
     * @param ClassMetadata $meta
     *
     * @return string
     */
    public function getRootObjectClass($meta);

    /**
     * Get the object manager.
     *
     * @return ObjectManager
     */
    public function getObjectManager();

    /**
     * Gets the state of an object from the unit of work.
     *
     * @param object $uow    The UnitOfWork object as provided by the object manager
     * @param object $object
     *
     * @return int The object state as reported by the unit of work
     */
    public function getObjectState($uow, $object);

    /**
     * Gets the changeset for an object from the unit of work.
     *
     * @param object $uow    The UnitOfWork as provided by the object manager
     * @param object $object
     *
     * @return array[]
     */
    public function getObjectChangeSet($uow, $object);

    /**
     * Get the single identifier field name.
     *
     * @param ClassMetadata $meta
     *
     * @return string
     */
    public function getSingleIdentifierFieldName($meta);

    /**
     * Computes the changeset of an individual object, independently of the
     * computeChangeSets() routine that is used at the beginning of a unit
     * of work's commit.
     *
     * @param object        $uow    The UnitOfWork as provided by the object manager
     * @param ClassMetadata $meta
     * @param object        $object
     *
     * @return void
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object);

    /**
     * Gets the currently scheduled object updates from the unit of work.
     *
     * @param object $uow The UnitOfWork as provided by the object manager
     *
     * @return array
     */
    public function getScheduledObjectUpdates($uow);

    /**
     * Gets the currently scheduled object insertions in the unit of work.
     *
     * @param object $uow The UnitOfWork as provided by the object manager
     *
     * @return array
     */
    public function getScheduledObjectInsertions($uow);

    /**
     * Gets the currently scheduled object deletions in the unit of work.
     *
     * @param object $uow The UnitOfWork as provided by the object manager
     *
     * @return array
     */
    public function getScheduledObjectDeletions($uow);

    /**
     * Sets a property value of the original data array of an object.
     *
     * @param object $uow      The UnitOfWork as provided by the object manager
     * @param string $oid
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     */
    public function setOriginalObjectProperty($uow, $oid, $property, $value);

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param object $uow The UnitOfWork as provided by the object manager
     * @param string $oid the object's OID
     */
    public function clearObjectChangeSet($uow, $oid);
}
