<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Category;
use Gedmo\Tests\Mapping\Fixture\ClosureCategory;
use Gedmo\Tests\Mapping\Fixture\MaterializedPathCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category as YamlCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\ClosureCategory as YamlClosureCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\MaterializedPathCategory as YamlMaterializedPathCategory;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosureWithoutMapping;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TreeMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    private TreeListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();

        $chain = new MappingDriverChain();

        if (class_exists(YamlDriver::class)) {
            $chain->addDriver(new YamlDriver(__DIR__.'/Driver/Yaml'), 'Gedmo\Tests\Mapping\Fixture\Yaml');
        }

        if (PHP_VERSION_ID >= 80000) {
            $annotationOrAttributeDriver = new AttributeDriver([]);
        } else {
            $annotationOrAttributeDriver = new AnnotationDriver(new AnnotationReader());
        }

        $chain->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Mapping\Fixture');
        $chain->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Tree\Fixture');
        $chain->addDriver($annotationOrAttributeDriver, 'Gedmo\Tree');

        $config->setMetadataDriverImpl($chain);

        $this->listener = new TreeListener();
        $this->listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager($config);
        $this->em->getEventManager()->addEventSubscriber($this->listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataNestedMappingObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [Category::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [Category::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlCategory::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataNestedMappingObject
     */
    public function testNestedMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Tree');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('left', $config);
        static::assertSame('left', $config['left']);
        static::assertArrayHasKey('right', $config);
        static::assertSame('right', $config['right']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('root', $config);
        static::assertSame('rooted', $config['root']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('nested', $config['strategy']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataClosureMappingObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [ClosureCategory::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [ClosureCategory::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlClosureCategory::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataClosureMappingObject
     */
    public function testClosureMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Tree');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('closure', $config['strategy']);
        static::assertArrayHasKey('closure', $config);
        static::assertSame(CategoryClosureWithoutMapping::class, $config['closure']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataMaterializedPathMappingObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [MaterializedPathCategory::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [MaterializedPathCategory::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlMaterializedPathCategory::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataMaterializedPathMappingObject
     */
    public function testMaterializedPathMapping(string $className): void
    {
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Tree');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('strategy', $config);
        static::assertSame('materializedPath', $config['strategy']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('activate_locking', $config);
        static::assertTrue($config['activate_locking']);
        static::assertArrayHasKey('locking_timeout', $config);
        static::assertSame(3, $config['locking_timeout']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('path', $config);
        static::assertSame('path', $config['path']);
        static::assertArrayHasKey('path_separator', $config);
        static::assertSame(',', $config['path_separator']);
    }
}
