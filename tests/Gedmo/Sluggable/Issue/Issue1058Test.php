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
use Gedmo\Tests\Sluggable\Fixture\Issue1058\Page;
use Gedmo\Tests\Sluggable\Fixture\Issue1058\User;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue1058Test extends ORMTestCase
{
    private const PAGE = Page::class;
    private const USER = User::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::PAGE,
            self::USER,
        ]);
    }

    /**
     * @group issue1058
     */
    public function testShouldHandleUniqueConstraintsBasedOnRelation(): void
    {
        $userFoo = new User();
        $this->em->persist($userFoo);

        $userBar = new User();
        $this->em->persist($userBar);

        $this->em->flush();

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userFoo);

        $this->em->persist($page);
        $this->em->flush();
        static::assertSame('the-title', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userBar);

        $this->em->persist($page);
        $this->em->flush();
        static::assertSame('the-title', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userBar);

        $this->em->persist($page);
        $this->em->flush();
        static::assertSame('the-title-1', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userFoo);

        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('the-title-1', $page->getSlug());
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
