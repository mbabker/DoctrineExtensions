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
use Gedmo\Tests\Sluggable\Fixture\Issue116\Country;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue116Test extends ORMTestCase
{
    private const TARGET = Country::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::TARGET,
        ]);
    }

    public function testSlugGeneration(): void
    {
        $country = new Country();
        $country->setOriginalName('New Zealand');

        $this->em->persist($country);
        $this->em->flush();

        static::assertSame('new-zealand', $country->getAlias());
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
