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
use Gedmo\Tests\Sluggable\Fixture\Article;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class CustomTransliteratorTest extends ORMTestCase
{
    private const ARTICLE = Article::class;

    private SluggableListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE,
        ]);
    }

    public function testStandardTransliteratorFailsOnChineseCharacters(): void
    {
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneBy(['code' => 'zh']);

        static::assertSame('bei-jing-zh', $chinese->getSlug());
    }

    public function testCanUseCustomTransliterator(): void
    {
        $this->listener->setTransliterator([Transliterator::class, 'transliterate']);

        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneBy(['code' => 'zh']);

        static::assertSame('bei-jing', $chinese->getSlug());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $this->listener = new SluggableListener();

        $evm->addEventSubscriber($this->listener);
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
        $chinese = new Article();
        $chinese->setTitle('北京');
        $chinese->setCode('zh');

        $this->em->persist($chinese);
        $this->em->flush();
        $this->em->clear();
    }
}

final class Transliterator
{
    public static function transliterate(string $text, string $separator, object $object): string
    {
        return 'Bei Jing';
    }
}
