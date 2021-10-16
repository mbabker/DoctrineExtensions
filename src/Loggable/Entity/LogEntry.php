<?php

namespace Gedmo\Loggable\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;

/**
 * Entity object for log entries for the ORM.
 *
 * @ORM\Table(
 *     name="ext_log_entries",
 *     options={"row_format":"DYNAMIC"},
 *  indexes={
 *      @ORM\Index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *      @ORM\Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
#[ORM\Entity(repositoryClass: LogEntryRepository::class)]
#[ORM\Table(name: 'ext_log_entries', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(name: 'log_class_lookup_idx', columns: ['object_class'])]
#[ORM\Index(name: 'log_date_lookup_idx', columns: ['logged_at'])]
#[ORM\Index(name: 'log_user_lookup_idx', columns: ['username'])]
#[ORM\Index(name: 'log_version_lookup_idx', columns: ['object_id', 'object_class', 'version'])]
class LogEntry extends MappedSuperclass\AbstractLogEntry
{
}
