<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Tree\Fixture\BehavioralCategory;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MappingTest extends ORMTestCase
{
    private const TEST_ENTITY_CATEGORY = BehavioralCategory::class;
    private const TEST_ENTITY_TRANSLATION = Translation::class;

    private TimestampableListener $timestampableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TEST_ENTITY_CATEGORY,
            self::TEST_ENTITY_TRANSLATION,
        ]);
    }

    public function testNoCacheImplementationMapping(): void
    {
        $food = new BehavioralCategory();
        $food->setTitle('Food');

        $this->em->persist($food);
        $this->em->flush();

        // assertion checks if configuration is read correctly without a cache driver
        $conf = $this->timestampableListener->getConfiguration($this->em, self::TEST_ENTITY_CATEGORY);

        static::assertCount(0, $conf);
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $this->timestampableListener = new TimestampableListener();

        $evm->addEventSubscriber(new TranslatableListener());
        $evm->addEventSubscriber($this->timestampableListener);
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());
    }

    protected function addMetadataDriversToChain(MappingDriverChain $driver): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $annotationOrAttributeDriver = $this->createAttributeDriver();
        } elseif (class_exists(AnnotationDriver::class)) {
            $annotationOrAttributeDriver = $this->createAnnotationDriver();
        } else {
            static::markTestSkipped('Test requires PHP 8 or doctrine/orm with annotations support.');
        }

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Tree\Fixture');
        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Translatable\Entity');
    }
}
