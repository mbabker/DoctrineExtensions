<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\Tool\QueryLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Yaml\Yaml;

/**
 * Base test case for functional tests using the ORM entity manager.
 */
abstract class ORMTestCase extends TestCase
{
    protected QueryLogger $queryLogger;

    protected ArrayAdapter $metadataCache;

    protected EntityManager $em;

    private SchemaTool $schemaTool;

    protected function setUp(): void
    {
        $this->resetEnvironment();
    }

    final protected function resetEnvironment(): void
    {
        $this->queryLogger = new QueryLogger();
        $this->metadataCache = new ArrayAdapter();
        $this->em = $this->createEntityManager();
        $this->schemaTool = new SchemaTool($this->em);
    }

    final protected function createEntityManager(): EntityManager
    {
        $config = $this->createConfiguration();
        $evm = $this->createEventManager();

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        return new EntityManager($connection, $config, $evm);
    }

    final protected function createConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxies');
        $config->setMetadataDriverImpl($this->createMetadataDriver());
        $config->setMetadataCache($this->metadataCache);
        $config->setMiddlewares([
            new Middleware($this->queryLogger),
        ]);

        $this->modifyConfiguration($config);

        return $config;
    }

    /**
     * Optional hook point for test cases to modify the configuration object before
     * creating the connection and entity manager.
     */
    protected function modifyConfiguration(Configuration $config): void
    {
    }

    final protected function createEventManager(): EventManager
    {
        $evm = new EventManager();

        $this->modifyEventManager($evm);

        return $evm;
    }

    /**
     * Optional hook point for test cases to modify the event manager before
     * creating the entity manager.
     */
    protected function modifyEventManager(EventManager $evm): void
    {
    }

    final protected function createMetadataDriver(): MappingDriver
    {
        $driver = new MappingDriverChain();

        $this->addMetadataDriversToChain($driver);

        return $driver;
    }

    /**
     * Optional hook point for test cases to add mapping drivers to the chained
     * mapping driver that will be used as the metadata driver for the entity manager.
     */
    protected function addMetadataDriversToChain(MappingDriverChain $driver): void
    {
    }

    /**
     * Creates the schema for the provided list of objects.
     *
     * @param string[] $classes
     *
     * @phpstan-param list<class-string> $classes
     */
    final protected function createSchemaForObjects(array $classes): void
    {
        $schema = array_map(fn (string $class): ClassMetadata => $this->em->getClassMetadata($class), $classes);

        if ($schema) {
            $this->schemaTool->createSchema($schema);
        }
    }

    final protected function createAnnotationDriver(): AnnotationDriver
    {
        if (!class_exists(AnnotationDriver::class)) {
            static::fail('Test requires ORM annotation support.');
        }

        if (!interface_exists(Reader::class)) {
            static::fail('Test requires doctrine/annotations to be installed.');
        }

        return new AnnotationDriver(new AnnotationReader(), []);
    }

    final protected function createAttributeDriver(): AttributeDriver
    {
        if (PHP_VERSION_ID < 80000) {
            static::fail('Test requires PHP 8.0 or later.');
        }

        return new AttributeDriver([]);
    }

    /**
     * Creates an XML mapping driver with XSD validation disabled.
     *
     * @param string|array<int, string>|FileLocator $locator a FileLocator or one/multiple paths where mapping documents can be found
     */
    final protected function createXmlDriver($locator): XmlDriver
    {
        return new XmlDriver($locator, XmlDriver::DEFAULT_FILE_EXTENSION, false);
    }

    /**
     * @param string|array<int, string>|FileLocator $locator a FileLocator or one/multiple paths where mapping documents can be found
     */
    final protected function createYamlDriver($locator): YamlDriver
    {
        if (!class_exists(YamlDriver::class)) {
            static::fail('Test requires ORM YAML support.');
        }

        if (!class_exists(Yaml::class)) {
            static::fail('Test requires symfony/yaml to be installed.');
        }

        return new YamlDriver($locator);
    }
}
