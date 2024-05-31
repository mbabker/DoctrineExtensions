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
use Gedmo\Tests\Sluggable\Fixture\Identifier;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableIdentifierTest extends ORMTestCase
{
    private const TARGET = Identifier::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TARGET,
        ]);
    }

    public function testShouldBePossibleToSlugIdentifiers(): void
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertSame('sport', $sport->getId());
    }

    public function testShouldPersistMultipleNonConflictingIdentifierSlugs(): void
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);

        $sport2 = new Identifier();
        $sport2->setTitle('Sport');

        $this->em->persist($sport2);
        $this->em->flush();

        static::assertSame('sport', $sport->getId());
        static::assertSame('sport_1', $sport2->getId());
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
}
