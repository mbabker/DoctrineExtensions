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
use Gedmo\Tests\Sluggable\Fixture\Handler\People\Occupation;
use Gedmo\Tests\Sluggable\Fixture\Handler\People\Person;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class BothSlugHandlerTest extends ORMTestCase
{
    private const OCCUPATION = Occupation::class;
    private const PERSON = Person::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::OCCUPATION,
            self::PERSON,
        ]);

        $this->populate();
    }

    public function testSlugGeneration(): void
    {
        $repo = $this->em->getRepository(self::PERSON);

        $herzult = $repo->findOneBy(['name' => 'Herzult']);
        static::assertSame('web/developer/php/herzult', $herzult->getSlug());

        $gedi = $repo->findOneBy(['name' => 'Gedi']);
        static::assertSame('web/developer/gedi', $gedi->getSlug());

        $hurty = $repo->findOneBy(['name' => 'Hurty']);
        static::assertSame('singer/hurty', $hurty->getSlug());
    }

    public function testSlugUpdates(): void
    {
        $repo = $this->em->getRepository(self::PERSON);

        $gedi = $repo->findOneBy(['name' => 'Gedi']);
        $gedi->setName('Upd Gedi');
        $this->em->persist($gedi);
        $this->em->flush();

        static::assertSame('web/developer/upd-gedi', $gedi->getSlug());

        $artist = $this->em->getRepository(self::OCCUPATION)->findOneBy(['title' => 'Singer']);
        $artist->setTitle('Artist');

        $this->em->persist($artist);
        $this->em->flush();

        $gedi->setOccupation($artist);
        $this->em->persist($gedi);
        $this->em->flush();

        static::assertSame('artist/upd-gedi', $gedi->getSlug());

        $hurty = $repo->findOneBy(['name' => 'Hurty']);
        static::assertSame('artist/hurty', $hurty->getSlug());
    }

    public function test1093(): void
    {
        $personRepo = $this->em->getRepository(self::PERSON);
        $occupationRepo = $this->em->getRepository(self::OCCUPATION);

        $herzult = $personRepo->findOneBy(['name' => 'Herzult']);
        static::assertSame('web/developer/php/herzult', $herzult->getSlug());

        $developer = $occupationRepo->findOneBy(['title' => 'Developer']);
        $developer->setTitle('Enthusiast');

        $this->em->persist($developer);
        $this->em->flush();

        // Works (but is not updated in the actual DB)
        $herzult = $personRepo->findOneBy(['name' => 'Herzult']);
        static::assertSame('web/enthusiast/php/herzult', $herzult->getSlug());

        $this->em->clear();

        // Does not work.
        $herzult = $personRepo->findOneBy(['name' => 'Herzult']);
        static::assertSame('web/enthusiast/php/herzult', $herzult->getSlug());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $evm->addEventSubscriber(new TreeListener());
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
        $repo = $this->em->getRepository(self::OCCUPATION);

        $web = new Occupation();
        $web->setTitle('Web');

        $developer = new Occupation();
        $developer->setTitle('Developer');

        $designer = new Occupation();
        $designer->setTitle('Designer');

        $php = new Occupation();
        $php->setTitle('PHP');

        $singer = new Occupation();
        $singer->setTitle('Singer');

        $rock = new Occupation();
        $rock->setTitle('Rock');

        // Singer
        // > Hurty
        // -- Rock
        // Web
        // -- Designer
        // -- Developer
        // -- -- PHP
        // -- -- > Herzult
        // -- > Gedi
        $repo
            ->persistAsFirstChild($web)
            ->persistAsFirstChild($singer)
            ->persistAsFirstChildOf($developer, $web)
            ->persistAsFirstChildOf($designer, $web)
            ->persistAsLastChildOf($php, $developer)
            ->persistAsLastChildOf($rock, $singer)
        ;

        $herzult = new Person();
        $herzult->setName('Herzult');
        $herzult->setOccupation($php);
        $this->em->persist($herzult);

        $gedi = new Person();
        $gedi->setName('Gedi');
        $gedi->setOccupation($developer);
        $this->em->persist($gedi);

        $hurty = new Person();
        $hurty->setName('Hurty');
        $hurty->setOccupation($singer);
        $this->em->persist($hurty);

        $this->em->flush();
    }
}
