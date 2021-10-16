<?php

namespace Gedmo\Translatable\Hydrator\ORM;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator as BaseObjectHydrator;
use Gedmo\Translatable\TranslatableListener;

/**
 * Extended object hydrator supporting a TranslationWalker to ensure
 * translatable fields are not re-translated when loaded.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ObjectHydrator extends BaseObjectHydrator
{
    /**
     * State of skipOnLoad for the listener between hydrations
     *
     * @see ObjectHydrator::prepare()
     * @see ObjectHydrator::cleanup()
     *
     * @var bool
     */
    private $savedSkipOnLoad;

    /**
     * {@inheritdoc}
     */
    protected function prepare()
    {
        $listener = $this->getTranslatableListener();
        $this->savedSkipOnLoad = $listener->isSkipOnLoad();
        $listener->setSkipOnLoad(true);
        parent::prepare();
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanup()
    {
        parent::cleanup();
        $listener = $this->getTranslatableListener();
        $listener->setSkipOnLoad(null !== $this->savedSkipOnLoad ? $this->savedSkipOnLoad : false);
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @return TranslatableListener
     *
     * @throws \Gedmo\Exception\RuntimeException if the listener is not registered
     */
    protected function getTranslatableListener()
    {
        $translatableListener = null;
        foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof TranslatableListener) {
                    $translatableListener = $listener;
                    break;
                }
            }
            if ($translatableListener) {
                break;
            }
        }

        if (is_null($translatableListener)) {
            throw new \Gedmo\Exception\RuntimeException('The translation listener could not be found');
        }

        return $translatableListener;
    }
}
