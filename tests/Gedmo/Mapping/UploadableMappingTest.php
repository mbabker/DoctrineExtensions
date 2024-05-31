<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Uploadable as AnnotatedUploadable;
use Gedmo\Tests\Mapping\Fixture\Xml\Uploadable as XmlUploadable;
use Gedmo\Tests\Mapping\Fixture\Yaml\Uploadable as YamlUploadable;
use Gedmo\Uploadable\Mapping\Validator;
use Gedmo\Uploadable\UploadableListener;

/**
 * These are mapping tests for Uploadable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class UploadableMappingTest extends MappingORMTestCase
{
    public static function setUpBeforeClass(): void
    {
        Validator::$enableMimeTypesConfigException = false;
    }

    public static function tearDownAfterClass(): void
    {
        Validator::$enableMimeTypesConfigException = true;
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataUploadableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlUploadable::class];

        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedUploadable::class];
        }

        if (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedUploadable::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlUploadable::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataUploadableObject
     */
    public function testUploadableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Uploadable');
        $config = $this->metadataCache->getItem($cacheId)->get();

        static::assertTrue($config['uploadable']);
        static::assertTrue($config['allowOverwrite']);
        static::assertTrue($config['appendNumber']);
        static::assertSame('/my/path', $config['path']);
        static::assertSame('getPath', $config['pathMethod']);
        static::assertSame('mimeType', $config['fileMimeTypeField']);
        static::assertSame('path', $config['filePathField']);
        static::assertSame('size', $config['fileSizeField']);
        static::assertSame('callbackMethod', $config['callback']);
        static::assertSame(Validator::FILENAME_GENERATOR_SHA1, $config['filenameGenerator']);
        static::assertSame(1500.0, $config['maxSize']);
        static::assertContains('text/plain', $config['allowedTypes']);
        static::assertContains('text/css', $config['allowedTypes']);
        static::assertContains('video/jpeg', $config['disallowedTypes']);
        static::assertContains('text/html', $config['disallowedTypes']);
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $listener = new UploadableListener();
        $listener->setCacheItemPool($this->metadataCache);

        $evm->addEventSubscriber($listener);
    }
}
