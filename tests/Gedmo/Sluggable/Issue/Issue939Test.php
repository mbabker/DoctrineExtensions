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
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\Sluggable\Fixture\Issue939\Article;
use Gedmo\Tests\Sluggable\Fixture\Issue939\Category;
use Gedmo\Tests\Sluggable\Fixture\Issue939\SluggableListener as SluggableListenerIssue939;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue939Test extends ORMTestCase
{
    private const ARTICLE = Article::class;
    private const CATEGORY = Category::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE,
            self::CATEGORY,
        ]);
    }

    public function testSlugGeneration(): void
    {
        $category = new Category();
        $category->setTitle('Misc articles');
        $this->em->persist($category);

        $article = new Article();
        $article->setTitle('Is there water on the moon?');
        $article->setCategory($category);

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('Is there water on the moon?', $article->getSlug());
        static::assertSame('misc-articles', $category->getSlug());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $evm->addEventSubscriber(new SluggableListenerIssue939());
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
