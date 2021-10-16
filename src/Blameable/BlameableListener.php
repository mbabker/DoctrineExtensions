<?php

namespace Gedmo\Blameable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * The Blameable listener sets blame information
 * on objects when created and updated.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableListener extends AbstractTrackingListener
{
    /**
     * The user to be blamed.
     *
     * The user may be a string, an object with a `getUsername()` method, or a Stringable object.
     *
     * @var mixed
     */
    protected $user;

    /**
     * Get the username to use on a blameable field.
     *
     * @param ClassMetadata    $meta
     * @param string           $field
     * @param AdapterInterface $eventAdapter
     *
     * @return mixed
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        if ($meta->hasAssociation($field)) {
            if (null !== $this->user && !is_object($this->user)) {
                throw new InvalidArgumentException('Blame is reference, user must be an object');
            }

            return $this->user;
        }

        // ok so it's not an association, then it is a string
        if (is_object($this->user)) {
            if (method_exists($this->user, 'getUsername')) {
                return (string) $this->user->getUsername();
            }
            if (method_exists($this->user, '__toString')) {
                return $this->user->__toString();
            }
            throw new InvalidArgumentException('Field expects string, user must be a string, or object should have method getUsername or __toString');
        }

        return $this->user;
    }

    /**
     * Set the user data for the user to be blamed.
     *
     * The user may be a string, an object with a `getUsername()` method, or a Stringable object.
     *
     * @param mixed $user
     */
    public function setUserValue($user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
