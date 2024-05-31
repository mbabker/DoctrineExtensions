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
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\Issue449\Article;

/**
 * These are tests for Sluggable behavior
 *
 * @author Craig Marvelley <craig.marvelley@gmail.com>
 *
 * @see http://marvelley.com
 */
final class Issue449Test extends ORMTestCase
{
    private const TARGET = Article::class;
    private const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    private SoftDeleteableListener $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TARGET,
        ]);

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testShouldBuildUniqueSlugAfterSoftDeleteFilterIsDisabled(): void
    {
        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();

        $slug = $article->getSlug();

        $this->em->remove($article);
        $this->em->flush();

        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        static::assertNotSame($slug, $article->getSlug());
    }

    protected function modifyConfiguration(Configuration $config): void
    {
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $listener = new SluggableListener();
        $listener->addManagedFilter(self::SOFT_DELETEABLE_FILTER_NAME, true);

        $evm->addEventSubscriber($listener);
        $evm->addEventSubscriber(new SoftDeleteableListener());
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
