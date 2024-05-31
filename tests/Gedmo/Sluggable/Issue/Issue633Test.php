<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\Issue633\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Derek Clapham <derek.clapham@gmail.com>
 */
final class Issue633Test extends ORMTestCase
{
    private const TARGET = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TARGET,
        ]);
    }

    public function testShouldHandleUniqueBasedSlug(): void
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);
        $this->em->flush();

        static::assertSame('unique-to-code', $test->getSlug());

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);
        $this->em->flush();

        static::assertSame('unique-to-code', $test2->getSlug());

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        static::assertSame('unique-to-code-1', $test3->getSlug());
    }

    public function testHandlePersistedSlugsForUniqueBased(): void
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        static::assertSame('unique-to-code', $test->getSlug());
        static::assertSame('unique-to-code', $test2->getSlug());
        static::assertSame('unique-to-code-1', $test3->getSlug());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $evm->addEventSubscriber(new SluggableListener());
    }

    protected function addMetadataDriversToChain(MappingDriverChain $driver): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $nestedDriver = $this->createAttributeDriver();
        } elseif (class_exists(YamlDriver::class)) {
            $nestedDriver = $this->createYamlDriver(__DIR__.'/../Fixture/Issue116/Mapping');
        } else {
            static::markTestSkipped('Test requires PHP 8 or doctrine/orm with YAML support.');
        }

        $driver->addDriver($nestedDriver, 'Gedmo\Tests\Sluggable\Fixture');
    }
}
