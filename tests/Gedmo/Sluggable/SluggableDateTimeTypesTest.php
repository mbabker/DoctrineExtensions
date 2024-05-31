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
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDate;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateImmutable;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTime;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTimeImmutable;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTimeTz;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTimeTzImmutable;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableDateTimeTypesTest extends ORMTestCase
{
    private const ARTICLE_DATE = ArticleDate::class;
    private const ARTICLE_DATE_IMMUTABLE = ArticleDateImmutable::class;
    private const ARTICLE_DATETIME = ArticleDateTime::class;
    private const ARTICLE_DATETIME_IMMUTABLE = ArticleDateTimeImmutable::class;
    private const ARTICLE_DATETIME_TZ = ArticleDateTimeTz::class;
    private const ARTICLE_DATETIME_TZ_IMMUTABLE = ArticleDateTimeTzImmutable::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE_DATE,
            self::ARTICLE_DATE_IMMUTABLE,
            self::ARTICLE_DATETIME,
            self::ARTICLE_DATETIME_IMMUTABLE,
            self::ARTICLE_DATETIME_TZ,
            self::ARTICLE_DATETIME_TZ_IMMUTABLE,
        ]);
    }

    public static function dataMutableDateTimeTypes(): \Generator
    {
        yield 'Entity with date field' => [self::ARTICLE_DATE, true];
        yield 'Entity with datetime field' => [self::ARTICLE_DATETIME, true];
        yield 'Entity with datetimetz field' => [self::ARTICLE_DATETIME_TZ, true];
    }

    public static function dataImmutableDateTimeTypes(): \Generator
    {
        yield 'Entity with date_immutable field' => [self::ARTICLE_DATE_IMMUTABLE, false];
        yield 'Entity with datetime_immutable field' => [self::ARTICLE_DATETIME_IMMUTABLE, false];
        yield 'Entity with datetimetz_immutable field' => [self::ARTICLE_DATETIME_TZ_IMMUTABLE, false];
    }

    /**
     * @dataProvider dataMutableDateTimeTypes
     * @dataProvider dataImmutableDateTimeTypes
     */
    public function testShouldBuildSlugWithAllDateTimeTypes(string $entityClass, bool $isMutable): void
    {
        $entity = new $entityClass();
        $entity->setTitle('the title');
        $entity->setCreatedAt($isMutable ? new \DateTime('2022-04-01') : new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('the-title-2022-04-01', $entity->getSlug(), 'with date');
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
