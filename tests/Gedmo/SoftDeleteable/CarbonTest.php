<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Carbon\Carbon;
use Carbon\Doctrine\DateTimeType as CarbonDateTimeType;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\DateTimeType as DBALDateTimeType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Article;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Comment;

final class CarbonTest extends ORMTestCase
{
    private const ARTICLE_CLASS = Article::class;
    private const COMMENT_CLASS = Comment::class;
    private const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    public static function setUpBeforeClass(): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, CarbonDateTimeType::class);
    }

    public static function tearDownAfterClass(): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, DBALDateTimeType::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE_CLASS,
            self::COMMENT_CLASS,
        ]);

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testSoftDeleteable(): void
    {
        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        static::assertNull($art->getDeletedAt());
        static::assertNull($comment->getDeletedAt());

        $this->em->remove($art);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);
        static::assertNull($art);
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertNull($comment);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $art = $repo->findOneBy([$field => $value]);
        static::assertIsObject($art);
        static::assertIsObject($art->getDeletedAt());
        static::assertInstanceOf(Carbon::class, $art->getDeletedAt());
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertIsObject($comment);
        static::assertIsObject($comment->getDeletedAt());
        static::assertInstanceOf(Carbon::class, $comment->getDeletedAt());
    }

    protected function modifyConfiguration(Configuration $config): void
    {
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
    }

    protected function modifyEventManager(EventManager $evm): void
    {
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

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\SoftDeleteable\Fixture\Entity');
    }
}
