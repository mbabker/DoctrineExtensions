<?php

namespace Gedmo\Timestampable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;

/**
 * The Blameable listener sets created and updated
 * timestamps on objects.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener extends AbstractTrackingListener
{
    /**
     * @param ClassMetadata        $meta
     * @param string               $field
     * @param TimestampableAdapter $eventAdapter
     *
     * @return mixed
     */
    protected function getFieldValue($meta, $field, $eventAdapter)
    {
        return $eventAdapter->getDateValue($meta, $field);
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
