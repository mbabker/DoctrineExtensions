<?php

namespace Gedmo\Loggable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * Base Document object for log entries for the MongoDB ODM.
 *
 * @MongoODM\MappedSuperclass
 */
abstract class AbstractLogEntry
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
    protected $action;

    /**
     * @var \DateTime
     *
     * @MongoODM\Field(type="date")
     */
    protected $loggedAt;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    protected $objectId;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $objectClass;

    /**
     * @var int
     *
     * @MongoODM\Field(type="int")
     */
    protected $version;

    /**
     * @var array
     *
     * @MongoODM\Field(type="hash", nullable=true)
     */
    protected $data;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string", nullable=true)
     */
    protected $username;

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
     * Get the action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the action.
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get the object class.
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set the object class.
     *
     * @param string $objectClass
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    /**
     * Get the object identifier.
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set the object identifier.
     *
     * @param string $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * Get the username of the user who performed the action.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the username of the user who performed the action.
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get the time the action was logged at.
     *
     * @return \DateTime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set the time the action was logged at to "now".
     */
    public function setLoggedAt()
    {
        $this->loggedAt = new \DateTime();
    }

    /**
     * Get the data for the log entry.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data for the log entry.
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the object version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the object version.
     *
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
