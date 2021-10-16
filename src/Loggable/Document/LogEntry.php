<?php

namespace Gedmo\Loggable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * Document object for log entries for the MongoDB ODM.
 *
 * @MongoODM\Document(
 *     repositoryClass="Gedmo\Loggable\Document\Repository\LogEntryRepository",
 *     indexes={
 *         @MongoODM\Index(keys={"objectId"="asc", "objectClass"="asc", "version"="asc"}),
 *         @MongoODM\Index(keys={"loggedAt"="asc"}),
 *         @MongoODM\Index(keys={"objectClass"="asc"}),
 *         @MongoODM\Index(keys={"username"="asc"})
 *     }
 * )
 */
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
}
