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
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\Article;
use Gedmo\Tests\Sluggable\Fixture\Doctrine\FakeFilter;

/**
 * These are tests for Sluggable behavior
 *
 * @author Florian Vilpoix <florianv@gmail.com>
 */
final class SluggableFiltersTest extends ORMTestCase
{
    private const TARGET = Article::class;

    private const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';
    private const FAKE_FILTER_NAME = 'fake-filter';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TARGET,
        ]);

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->getFilters()->enable(self::FAKE_FILTER_NAME);
    }

    public function testShouldSucceedWhenManagedFilterHasAlreadyBeenDisabled(): void
    {
        // disable one managed doctrine filter
        $this->em->getFilters()->disable(self::FAKE_FILTER_NAME);

        $slug = new Article();
        $slug->setCode('My code');
        $slug->setTitle('My title');

        $this->em->persist($slug);
        $this->em->flush();

        static::assertSame('my-title-my-code', $slug->getSlug());
    }

    protected function modifyConfiguration(Configuration $config): void
    {
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
        $config->addFilter(self::FAKE_FILTER_NAME, FakeFilter::class);
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $listener = new SluggableListener();
        $listener->addManagedFilter(self::SOFT_DELETEABLE_FILTER_NAME, true);
        $listener->addManagedFilter(self::FAKE_FILTER_NAME, true);

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
