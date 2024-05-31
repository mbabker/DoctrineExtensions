<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Address;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Person;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class HardRelationTest extends ORMTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            Person::class,
            Address::class,
        ]);

        $this->em->getFilters()->enable('softdelete');
    }

    public function testShouldCascadeSoftdeleteForHardRelations(): void
    {
        $address = new Address();
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person();
        $person->setName('Gedi');
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();

        // softdelete a hard relation
        $this->em->remove($address);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(Person::class)->findOneBy(['id' => $person->getId()]);
        static::assertNull($person, 'Softdelete should cascade to hard relation entity');
    }

    public function testShouldCascadeToInversedRelationAsWell(): void
    {
        $address = new Address();
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person();
        $person->setName('Gedi');
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();

        // softdelete a hard relation
        $this->em->remove($person);
        $this->em->flush();
        $this->em->clear();

        $address = $this->em->getRepository(Address::class)->findOneBy(['id' => $address->getId()]);
        static::assertNull($address, 'Softdelete should cascade to hard relation entity');
    }

    public function testShouldHandleTimeAwareSoftDeleteable(): void
    {
        $address = new Address();
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person();
        $person->setName('Gedi');
        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() + 15 * 3600))); // in an hour
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(Person::class)->findOneBy(['id' => $person->getId()]);
        static::assertNotNull($person, 'Should not be softdeleted');

        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() - 15 * 3600))); // in an hour
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(Person::class)->findOneBy(['id' => $person->getId()]);
        static::assertNull($person, 'Should be softdeleted');
    }

    protected function modifyConfiguration(Configuration $config): void
    {
        $config->addFilter('softdelete', SoftDeleteableFilter::class);
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
