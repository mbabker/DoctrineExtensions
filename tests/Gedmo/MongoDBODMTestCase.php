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
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use MongoDB\Client;
use MongoDB\Model\DatabaseInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Base test case for functional tests using the MongoDB ORM document manager.
 *
 * @requires extension mongodb
 */
abstract class MongoDBODMTestCase extends TestCase
{
    private const DATABASE_NAME = 'gedmo_extensions_test';

    protected ArrayAdapter $metadataCache;

    protected ?DocumentManager $dm = null;

    protected function setUp(): void
    {
        $this->resetEnvironment();
    }

    /**
     * Based on `Doctrine\ODM\MongoDB\Tests\BaseTest::tearDown()`.
     */
    protected function tearDown(): void
    {
        if (!$this->dm) {
            return;
        }

        $client = $this->dm->getClient();
        $databaseNames = array_map(
            static fn (DatabaseInfo $database): string => $database->getName(),
            iterator_to_array($client->listDatabases())
        );

        if (!in_array(self::DATABASE_NAME, $databaseNames, true)) {
            return;
        }

        $collections = $client->selectDatabase(self::DATABASE_NAME)->listCollections();

        foreach ($collections as $collection) {
            if (preg_match('#^system\.#', $collection->getName())) {
                continue;
            }

            $client->selectCollection(self::DATABASE_NAME, $collection->getName())->drop();
        }
    }

    final protected function resetEnvironment(): void
    {
        $this->metadataCache = new ArrayAdapter();
        $this->dm = $this->createDocumentManager();
    }

    final protected function createDocumentManager(): DocumentManager
    {
        $config = $this->createConfiguration();
        $evm = $this->createEventManager();

        $client = new Client($_ENV['MONGODB_SERVER'], [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);

        return DocumentManager::create($client, $config, $evm);
    }

    final protected function createConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl($this->createMetadataDriver());
        $config->setMetadataCache($this->metadataCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setAutoGenerateHydratorClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_extensions_test');

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
        \assert($this->dm instanceof DocumentManager);

        foreach ($classes as $class) {
            $this->dm->getSchemaManager()->createDocumentCollection($class);
        }

        $this->dm->getSchemaManager()->ensureIndexes();
    }

    /**
     * @param string|string[]|null $paths one or multiple paths where mapping classes can be found
     */
    final protected function createAnnotationDriver($paths = null): AnnotationDriver
    {
        if (!class_exists(AnnotationDriver::class)) {
            static::fail('Test requires MongoDB ODM annotation support.');
        }

        if (!interface_exists(Reader::class)) {
            static::fail('Test requires doctrine/annotations to be installed.');
        }

        return new AnnotationDriver(new AnnotationReader(), $paths);
    }

    /**
     * @param string|string[]|null $paths one or multiple paths where mapping classes can be found
     */
    final protected function createAttributeDriver($paths = null): AttributeDriver
    {
        if (PHP_VERSION_ID < 80000) {
            static::fail('Test requires PHP 8.0 or later.');
        }

        if (!class_exists(AttributeDriver::class)) {
            static::fail('Test requires MongoDB ODM attribute support.');
        }

        return new AttributeDriver($paths);
    }

    /**
     * @param string|array<int, string>|FileLocator $locator a FileLocator or one/multiple paths where mapping documents can be found
     */
    final protected function createXmlDriver($locator): XmlDriver
    {
        return new XmlDriver($locator);
    }
}
