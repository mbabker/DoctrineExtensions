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
use Gedmo\Tests\Sluggable\Fixture\Handler\Company;
use Gedmo\Tests\Sluggable\Fixture\Handler\User;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class UserRelativeSlugHandlerTest extends ORMTestCase
{
    private const USER = User::class;
    private const COMPANY = Company::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::USER,
            self::COMPANY,
        ]);
    }

    public function testRelativeSlug(): void
    {
        $company = new Company();
        $company->setTitle('KnpLabs');
        $this->em->persist($company);

        $gedi = new User();
        $gedi->setUsername('Gedi');
        $gedi->setCompany($company);
        $this->em->persist($gedi);

        $this->em->flush();

        static::assertSame('knplabs/gedi', $gedi->getSlug(), 'relative slug is invalid');

        $company->setTitle('KnpLabs Nantes');
        $this->em->persist($company);
        $this->em->flush();

        static::assertSame('knplabs-nantes/gedi', $gedi->getSlug(), 'relative slug is invalid');
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
}
