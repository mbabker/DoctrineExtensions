<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Mapping\Fixture\Sluggable;
use Gedmo\Tests\Mapping\Fixture\SuperClassExtension;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\EncoderListener;
use Psr\Cache\CacheItemPoolInterface;

final class MappingEventSubscriberTest extends MappingORMTestCase
{
    public function testGetMetadataFactoryCacheFromDoctrineForSluggable(): void
    {
        $cache = $this->getMetadataCacheFromMetadataFactory();

        $cacheKey = ExtensionMetadataFactory::getCacheId(Sluggable::class, 'Gedmo\Sluggable');

        static::assertFalse($cache->hasItem($cacheKey));

        (new SluggableListener())->getExtensionMetadataFactory($this->em)->getExtensionMetadata($this->em->getClassMetadata(Sluggable::class));

        static::assertTrue($cache->hasItem($cacheKey));
    }

    public function testGetMetadataFactoryCacheFromDoctrineForSuperClassExtension(): void
    {
        $cache = $this->getMetadataCacheFromMetadataFactory();

        $cacheKey = ExtensionMetadataFactory::getCacheId(SuperClassExtension::class, 'Gedmo\Tests\Mapping\Mock\Extension\Encoder');

        static::assertFalse($cache->hasItem($cacheKey));

        $config = (new EncoderListener())->getExtensionMetadataFactory($this->em)->getExtensionMetadata($this->em->getClassMetadata(SuperClassExtension::class));

        static::assertTrue($cache->hasItem($cacheKey));

        static::assertSame([
            'content' => [
                'type' => 'md5',
                'secret' => null,
            ],
        ], $config['encode']);

        // Reset the environment to force a new test with a new manager and cache
        $this->resetEnvironment();

        $cache = $this->getMetadataCacheFromMetadataFactory();

        static::assertFalse($cache->hasItem($cacheKey));

        $config = (new EncoderListener())->getExtensionMetadataFactory($this->em)->getExtensionMetadata($this->em->getClassMetadata(SuperClassExtension::class));

        static::assertTrue($cache->hasItem($cacheKey));

        static::assertSame([
            'content' => [
                'type' => 'md5',
                'secret' => null,
            ],
        ], $config['encode']);
    }

    private function getMetadataCacheFromMetadataFactory(): CacheItemPoolInterface
    {
        $metadataFactory = $this->em->getMetadataFactory();
        $getCache = \Closure::bind(
            static fn (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface => $metadataFactory->getCache(),
            null,
            \get_class($metadataFactory)
        );

        $cache = $getCache($metadataFactory);

        static::assertInstanceOf(CacheItemPoolInterface::class, $cache, 'The metadata factory does not have a configured cache.');

        return $cache;
    }
}
