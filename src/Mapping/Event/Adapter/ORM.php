<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Event\Adapter;

use Doctrine\Common\EventArgs;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata as EntityClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo as LegacyEntityClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs as BaseLifecycleArgs;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for ORM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @template TClassMetadata of EntityClassMetadata|LegacyEntityClassMetadata
 * @template TObjectManager of EntityManagerInterface
 * @template TUnitOfWork of UnitOfWork
 *
 * @template-implements AdapterInterface<TClassMetadata, TObjectManager, TUnitOfWork>
 */
class ORM implements AdapterInterface
{
    private ?EntityManagerInterface $em = null;

    /**
     * Holds the event object for the current event being processed
     */
    private ?EventArgs $args = null;

    /**
     * Holds the object the current event is referencing, if the event
     * is an object lifecycle event
     */
    private ?object $object = null;

    /**
     * @throws RuntimeException if an object manager other than the ORM's entity manager is provided
     */
    public function __construct(?ObjectManager $om = null)
    {
        if (null === $om) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'WIP',
                'Not providing the entity manager to the "%s" constructor is deprecated since gedmo/doctrine-extensions 3.x and will be required in version 4.0.',
                static::class
            );
        } elseif ($om instanceof EntityManagerInterface) {
            $this->em = $om;
        } else {
            throw new InvalidArgumentException(sprintf('The first parameter to the "%s" constructor must be an instance of %s or null, %s provided.', static::class, EntityManagerInterface::class, get_class($om)));
        }
    }

    /**
     * @param string            $method
     * @param array<int, mixed> $args
     *
     * @throws RuntimeException if the event object has not been set
     *
     * @return mixed
     *
     * @deprecated Calling the underlying event object through the event adapter is deprecated and will be removed in 4.0.
     */
    public function __call($method, $args)
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2409',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        );

        if (null === $this->args) {
            throw new RuntimeException('Event args must be set before calling its methods');
        }

        $method = str_replace('Object', $this->getDomainObjectName(), $method);

        return call_user_func_array([$this->args, $method], $args);
    }

    public function clearEventState(): void
    {
        $this->args = null;
        $this->object = null;
    }

    public function setEventState(EventArgs $args, ?object $object): void
    {
        $this->args = $args;
        $this->object = $object;
    }

    /**
     * @deprecated Use {@see setEventState} instead
     */
    public function setEventArgs(EventArgs $args)
    {
        $this->setEventState($args, $args instanceof BaseLifecycleArgs ? $args->getObject() : null);
    }

    public function getDomainObjectName()
    {
        return 'Entity';
    }

    public function getManagerName()
    {
        return 'ORM';
    }

    /**
     * @param TClassMetadata $meta
     */
    public function getRootObjectClass($meta)
    {
        return $meta->rootEntityName;
    }

    /**
     * Set the entity manager
     *
     * @param TObjectManager $em
     *
     * @return void
     *
     * @deprecated Injecting the entity manager after instantiation will not be supported in 4.0.
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return TObjectManager
     */
    public function getObjectManager()
    {
        if (null !== $this->em) {
            return $this->em;
        }

        // todo: for the next major release, remove everything past this

        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'WIP',
            'Retrieving the entity manager from the event object is deprecated. As of 4.0, it must be injected in the constructor.'
        );

        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

        if (\method_exists($this->args, 'getObjectManager')) {
            return $this->args->getObjectManager();
        }

        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2639',
            'Calling "%s()" on event args of class "%s" that does not implement "getObjectManager()" is deprecated since gedmo/doctrine-extensions 3.14'
            .' and will throw a "%s" error in version 4.0.',
            __METHOD__,
            get_class($this->args),
            \Error::class
        );

        return $this->args->getEntityManager();
    }

    public function getObject(): ?object
    {
        if (null !== $this->object) {
            return $this->object;
        }

        // todo: for the next major release, remove everything past this

        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'WIP',
            'Retrieving the subject object for an event from event object is deprecated. As of 4.0, it must be injected via %s::setEventState() before handling the event.',
            static::class
        );

        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

        // Only lifecycle events are object aware, so let's fail gracefully for other events that accidentally call this method
        if ($this->args instanceof BaseLifecycleArgs) {
            return $this->args->getObject();
        }

        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2639',
            'Calling "%s()" on event args of class "%s" that does not imeplement "getObject()" is deprecated since gedmo/doctrine-extensions 3.14'
            .' and will throw a "%s" error in version 4.0.',
            __METHOD__,
            get_class($this->args),
            \Error::class
        );

        if ($this->args instanceof LifecycleEventArgs) {
            return $this->args->getEntity();
        }

        return null;
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function getObjectState($uow, $object)
    {
        return $uow->getEntityState($object);
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }

    /**
     * @param TClassMetadata $meta
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }

    /**
     * @param TUnitOfWork    $uow
     * @param TClassMetadata $meta
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleEntityChangeSet($meta, $object);
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledEntityInsertions();
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledEntityDeletions();
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function setOriginalObjectProperty($uow, $object, $property, $value)
    {
        $uow->setOriginalEntityProperty(spl_object_id($object), $property, $value);
    }

    /**
     * @param TUnitOfWork $uow
     */
    public function clearObjectChangeSet($uow, $object)
    {
        $changeSet = &$uow->getEntityChangeSet($object);
        $changeSet = [];
    }

    /**
     * @deprecated use custom lifecycle event classes instead
     *
     * Creates an ORM specific LifecycleEventArgs
     *
     * @param object         $object
     * @param TObjectManager $entityManager
     *
     * @return LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($object, $entityManager)
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2649',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.15 and will be removed in version 4.0.',
            __METHOD__
        );

        if (!class_exists(LifecycleEventArgs::class)) {
            throw new \RuntimeException(sprintf('Cannot call %s() when using doctrine/orm >=3.0, use a custom lifecycle event class instead.', __METHOD__));
        }

        return new LifecycleEventArgs($object, $entityManager);
    }
}
