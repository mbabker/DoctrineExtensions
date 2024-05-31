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
use Gedmo\Tests\Sluggable\Fixture\Handler\Article;
use Gedmo\Tests\Sluggable\Fixture\Handler\ArticleRelativeSlug;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class RelativeSlugHandlerTest extends ORMTestCase
{
    private const SLUG = ArticleRelativeSlug::class;
    private const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::SLUG,
            self::ARTICLE,
        ]);

        $this->populate();
    }

    public function testSlugGeneration(): void
    {
        $repo = $this->em->getRepository(self::SLUG);

        $thomas = $repo->findOneBy(['title' => 'Thomas']);
        static::assertSame('sport-test/thomas', $thomas->getSlug());

        $jen = $repo->findOneBy(['title' => 'Jen']);
        static::assertSame('sport-test/jen', $jen->getSlug());

        $john = $repo->findOneBy(['title' => 'John']);
        static::assertSame('cars-code/john', $john->getSlug());

        $single = $repo->findOneBy(['title' => 'Single']);
        static::assertSame('single', $single->getSlug());
    }

    public function testUpdateOperations(): void
    {
        $repo = $this->em->getRepository(self::SLUG);

        $thomas = $repo->findOneBy(['title' => 'Thomas']);
        $thomas->setTitle('Ninja');
        $this->em->persist($thomas);
        $this->em->flush();

        static::assertSame('sport-test/ninja', $thomas->getSlug());

        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        $sport->setTitle('Martial Arts');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertSame('martial-arts-test/ninja', $thomas->getSlug());

        $jen = $repo->findOneBy(['title' => 'Jen']);
        static::assertSame('martial-arts-test/jen', $jen->getSlug());

        $cars = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Cars']);
        $jen->setArticle($cars);

        $this->em->persist($jen);
        $this->em->flush();

        static::assertSame('cars-code/jen', $jen->getSlug());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
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

    private function populate(): void
    {
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setCode('test');
        $this->em->persist($sport);

        $cars = new Article();
        $cars->setTitle('Cars');
        $cars->setCode('code');
        $this->em->persist($cars);

        $thomas = new ArticleRelativeSlug();
        $thomas->setTitle('Thomas');
        $thomas->setArticle($sport);
        $this->em->persist($thomas);

        $jen = new ArticleRelativeSlug();
        $jen->setTitle('Jen');
        $jen->setArticle($sport);
        $this->em->persist($jen);

        $john = new ArticleRelativeSlug();
        $john->setTitle('John');
        $john->setArticle($cars);
        $this->em->persist($john);

        $single = new ArticleRelativeSlug();
        $single->setTitle('Single');
        $this->em->persist($single);

        $this->em->flush();
    }
}
