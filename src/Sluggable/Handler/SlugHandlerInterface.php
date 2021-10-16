<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\SluggableListener;

/**
 * Interface defining a handler for the sluggable behavior.
 * Usage is intended only for internal access of the
 * Sluggable and should not be used elsewhere.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SlugHandlerInterface
{
    /**
     * Construct the handler.
     */
    public function __construct(SluggableListener $sluggable);

    /**
     * Callback on slug handlers before the decision is made
     * whether the slug needs to be recalculated.
     *
     * @param object $object
     * @param string $slug
     * @param bool   $needToChangeSlug
     *
     * @return void
     */
    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug);

    /**
     * Callback on slug handlers after the slug is built.
     *
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * Callback for slug handlers on slug completion
     *
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * @return bool whether this handler has already urlized the slug
     */
    public function handlesUrlization();

    /**
     * Validates the options for the handler.
     */
    public static function validate(array $options, ClassMetadata $meta);
}
