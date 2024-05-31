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
use Gedmo\Tests\Sluggable\Fixture\Issue1177\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue1177Test extends ORMTestCase
{
    private const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE,
        ]);
    }

    public function testShouldTryPreferedSlugFirst(): void
    {
        $article = new Article();
        $article->setTitle('the title with number 1');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-with-number-1', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        // the slug was 'the-title-with-number-2' before the fix here
        // despite the fact that there is no entity with slug 'the-title-with-number'
        static::assertSame('the-title-with-number', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-with-number-2', $article->getSlug());
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
