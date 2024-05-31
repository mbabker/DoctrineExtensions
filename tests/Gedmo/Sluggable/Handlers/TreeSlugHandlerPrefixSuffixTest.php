<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Handlers;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\Handler\TreeSlugPrefixSuffix;
use Gedmo\Tree\TreeListener;

final class TreeSlugHandlerPrefixSuffixTest extends ORMTestCase
{
    private const TARGET = TreeSlugPrefixSuffix::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TARGET,
        ]);
    }

    public function testPrefixSuffix(): void
    {
        $foo = new TreeSlugPrefixSuffix();
        $foo->setTitle('Foo');

        $bar = new TreeSlugPrefixSuffix();
        $bar->setTitle('Bar');
        $bar->setParent($foo);

        $baz = new TreeSlugPrefixSuffix();
        $baz->setTitle('Baz');
        $baz->setParent($bar);

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);

        $this->em->flush();

        static::assertSame('prefix.foo/bar/baz.suffix', $baz->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $evm->addEventSubscriber(new TreeListener());
        $evm->addEventSubscriber(new SluggableListener());
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
