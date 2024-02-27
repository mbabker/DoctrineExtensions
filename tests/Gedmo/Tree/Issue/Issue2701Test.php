<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Issue;

use Doctrine\Bundle\DoctrineBundle\Mapping\ClassMetadataFactory;
use Doctrine\Bundle\DoctrineBundle\Mapping\MappingDriver;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\Mapping\Driver\MappingDriver as MappingDriverInterface;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Issue2701\Category;
use Gedmo\Tree\TreeListener;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Factory\UlidFactory;

final class Issue2701Test extends BaseTestCaseORM
{
    private TreeListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $config = $this->getDefaultConfiguration();
        $config->setClassMetadataFactoryName(ClassMetadataFactory::class);

        $mappingDriver = $config->getMetadataDriverImpl();

        assert($mappingDriver instanceof MappingDriverInterface);

        $config->setMetadataDriverImpl(new MappingDriver($mappingDriver, $this->createIdGeneratorLocator()));

        Type::addType(UlidType::NAME, UlidType::class);

        $this->getDefaultMockSqliteEntityManager($evm, $config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // To avoid state bleedout between test cases, we will clear the type registry using brute force
        $refl = (new \ReflectionClass(Type::class))->getProperty('typeRegistry');
        $refl->setAccessible(true);
        $refl->setValue(Type::class, null);
    }

    public function testGetNextSiblingsWithoutIdentifierMethod(): void
    {
        $food = new Category();
        $food->setName('Food');

        $fruits = new Category();
        $fruits->setName('Fruits');
        $fruits->setParent($food);

        $vegetables = new Category();
        $vegetables->setName('Vegetables');
        $vegetables->setParent($food);

        $carrots = new Category();
        $carrots->setName('Carrots');
        $carrots->setParent($vegetables);

        $this->em->persist($food);
        $this->em->persist($fruits);
        $this->em->persist($vegetables);
        $this->em->persist($carrots);
        $this->em->flush();

        $categoryRepository = $this->em->getRepository(Category::class);

        static::assertTrue($categoryRepository->verify());
    }

    protected function createIdGeneratorLocator(): ContainerInterface
    {
        return new class(new UlidGenerator(new UlidFactory())) implements ContainerInterface {
            private UlidGenerator $ulidGenerator;

            public function __construct(UlidGenerator $ulidGenerator)
            {
                $this->ulidGenerator = $ulidGenerator;
            }

            public function get(string $id)
            {
                if ('doctrine.ulid_generator' === $id) {
                    return $this->ulidGenerator;
                }

                throw new class(sprintf('Service ID "%s" not found.', $id)) extends \InvalidArgumentException implements NotFoundExceptionInterface {
                };
            }

            public function has(string $id): bool
            {
                return 'doctrine.ulid_generator' === $id;
            }
        };
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Category::class];
    }
}
