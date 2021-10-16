<?php

namespace Gedmo\Sortable\Mapping\Event\Adapter;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Sortable\Mapping\Event\SortableAdapter;

/**
 * Doctrine event adapter for the MongoDB ODM, adapted
 * for the Sortable extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements SortableAdapter
{
    /**
     * @param array         $config
     * @param ClassMetadata $meta
     * @param array         $groups
     *
     * @return mixed
     */
    public function getMaxPosition(array $config, $meta, $groups)
    {
        $dm = $this->getObjectManager();

        $qb = $dm->createQueryBuilder($config['useObjectClass']);
        foreach ($groups as $group => $value) {
            if (is_object($value) && !$dm->getMetadataFactory()->isTransient(ClassUtils::getClass($value))) {
                $qb->field($group)->references($value);
            } else {
                $qb->field($group)->equals($value);
            }
        }
        $qb->sort($config['position'], 'desc');
        $document = $qb->getQuery()->getSingleResult();

        if ($document) {
            return $meta->getReflectionProperty($config['position'])->getValue($document);
        }

        return -1;
    }

    public function updatePositions($relocation, $delta, $config)
    {
        $dm = $this->getObjectManager();

        $delta = array_map('intval', $delta);

        $qb = $dm->createQueryBuilder($config['useObjectClass']);
        $qb->updateMany();
        $qb->field($config['position'])->inc($delta['delta']);
        $qb->field($config['position'])->gte($delta['start']);
        if ($delta['stop'] > 0) {
            $qb->field($config['position'])->lt($delta['stop']);
        }
        foreach ($relocation['groups'] as $group => $value) {
            if (is_object($value) && !$dm->getMetadataFactory()->isTransient(ClassUtils::getClass($value))) {
                $qb->field($group)->references($value);
            } else {
                $qb->field($group)->equals($value);
            }
        }

        $qb->getQuery()->execute();
    }
}
