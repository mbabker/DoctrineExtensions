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
use Gedmo\Sluggable\Sluggable;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\ConfigurationArticle;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableConfigurationTest extends ORMTestCase
{
    private const ARTICLE = ConfigurationArticle::class;

    private ?int $articleId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE,
        ]);

        $this->populate();
    }

    public function testInsertedNewSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        static::assertInstanceOf(Sluggable::class, $article);
        static::assertSame('the-title-my-code', $article->getSlug());
    }

    public function testNonUniqueSlugGeneration(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $article = new ConfigurationArticle();
            $article->setTitle('the title');
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();

            static::assertSame('the-title-my-code', $article->getSlug());
        }
    }

    public function testSlugLimit(): void
    {
        $long = 'the title the title the title the title the';
        $article = new ConfigurationArticle();
        $article->setTitle($long);
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $shorten = $article->getSlug();

        static::assertSame(32, strlen($shorten));
    }

    public function testNonUpdatableSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('the-title-my-code', $article->getSlug());
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
        $article = new ConfigurationArticle();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->articleId = $article->getId();
    }
}
