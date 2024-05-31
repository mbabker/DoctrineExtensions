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
use Gedmo\Tests\Sluggable\Fixture\Prefix;
use Gedmo\Tests\Sluggable\Fixture\PrefixWithTreeHandler;
use Gedmo\Tests\Sluggable\Fixture\Suffix;
use Gedmo\Tests\Sluggable\Fixture\SuffixWithTreeHandler;
use Gedmo\Tree\TreeListener;

final class SluggablePrefixSuffixTest extends ORMTestCase
{
    private const PREFIX = Prefix::class;
    private const SUFFIX = Suffix::class;
    private const SUFFIX_TREE = SuffixWithTreeHandler::class;
    private const PREFIX_TREE = PrefixWithTreeHandler::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::SUFFIX,
            self::PREFIX,
            self::SUFFIX_TREE,
            self::PREFIX_TREE,
        ]);
    }

    public function testPrefix(): void
    {
        $foo = new Prefix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        static::assertSame('test-foo', $foo->getSlug());
    }

    public function testSuffix(): void
    {
        $foo = new Suffix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        static::assertSame('foo.test', $foo->getSlug());
    }

    public function testNoDuplicateSuffixes(): void
    {
        $foo = new SuffixWithTreeHandler();
        $foo->setTitle('Foo');

        $bar = new SuffixWithTreeHandler();
        $bar->setTitle('Bar');
        $bar->setParent($foo);

        $baz = new SuffixWithTreeHandler();
        $baz->setTitle('Baz');
        $baz->setParent($bar);

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);
        $this->em->flush();

        static::assertSame('foo.test/bar.test/baz.test', $baz->getSlug());
    }

    public function testNoDuplicatePrefixes(): void
    {
        $foo = new PrefixWithTreeHandler();
        $foo->setTitle('Foo');

        $bar = new PrefixWithTreeHandler();
        $bar->setTitle('Bar');
        $bar->setParent($foo);

        $baz = new PrefixWithTreeHandler();
        $baz->setTitle('Baz');
        $baz->setParent($bar);

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);
        $this->em->flush();

        static::assertSame('test.foo/test.bar/test.baz', $baz->getSlug());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
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

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Sluggable\Fixture');
    }
}
