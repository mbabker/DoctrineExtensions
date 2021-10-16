<?php

namespace Gedmo\SoftDeleteable\Filter\ODM;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class SoftDeleteableFilter extends BsonFilter
{
    /**
     * @var SoftDeleteableListener|null
     */
    protected $listener;

    /**
     * @var DocumentManager|null
     */
    protected $documentManager;

    /**
     * @var array<class-string, bool>
     */
    protected $disabled = [];

    /**
     * Gets the criteria part to add to a query.
     *
     * @return array The criteria array, if one is available; empty array otherwise
     */
    public function addFilterCriteria(ClassMetadata $targetEntity): array
    {
        $class = $targetEntity->getName();
        if (array_key_exists($class, $this->disabled) && true === $this->disabled[$class]) {
            return [];
        } elseif (array_key_exists($targetEntity->rootDocumentName, $this->disabled) && true === $this->disabled[$targetEntity->rootDocumentName]) {
            return [];
        }

        $config = $this->getListener()->getConfiguration($this->getDocumentManager(), $targetEntity->name);

        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return [];
        }

        $column = $targetEntity->fieldMappings[$config['fieldName']];

        if (isset($config['timeAware']) && $config['timeAware']) {
            return [
                '$or' => [
                    [$column['fieldName'] => null],
                    [$column['fieldName'] => ['$gt' => new \DateTime('now')]],
                ],
            ];
        }

        return [
            $column['fieldName'] => null,
        ];
    }

    /**
     * @return SoftDeleteableListener
     *
     * @throws \RuntimeException if the listener is not registered
     */
    protected function getListener()
    {
        if (null === $this->listener) {
            $em = $this->getDocumentManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }

        return $this->listener;
    }

    /**
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        if (null === $this->documentManager) {
            $refl = new \ReflectionProperty(BsonFilter::class, 'dm');
            $refl->setAccessible(true);
            $this->documentManager = $refl->getValue($this);
        }

        return $this->documentManager;
    }

    /**
     * @param class-string $class
     */
    public function disableForDocument($class)
    {
        $this->disabled[$class] = true;
    }

    /**
     * @param class-string $class
     */
    public function enableForDocument($class)
    {
        $this->disabled[$class] = false;
    }
}
