<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\Position;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggablePositionTest extends ORMTestCase
{
    private const POSITION = Position::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::POSITION,
        ]);

        $this->populate();
    }

    public function testPositionedSlugOrder(): void
    {
        $meta = $this->em->getClassMetadata(self::POSITION);
        $repo = $this->em->getRepository(self::POSITION);

        $object = $repo->find(1);
        $slug = $meta->getReflectionProperty('slug')->getValue($object);

        static::assertSame('code-other-title-prop', $slug);
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $listener = new SluggableListener();

        $evm->addEventSubscriber($listener);
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

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Sluggable\Fixture');
    }

    private function populate(): void
    {
        $meta = $this->em->getClassMetadata(self::POSITION);

        $object = new Position();

        $meta->getReflectionProperty('title')->setValue($object, 'title');
        $meta->getReflectionProperty('prop')->setValue($object, 'prop');
        $meta->getReflectionProperty('code')->setValue($object, 'code');
        $meta->getReflectionProperty('other')->setValue($object, 'other');

        $this->em->persist($object);
        $this->em->flush();
    }
}
