<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\IpTraceable\IpTraceable;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tests\IpTraceable\Fixture\Article;
use Gedmo\Tests\IpTraceable\Fixture\Comment;
use Gedmo\Tests\IpTraceable\Fixture\TitledArticle;
use Gedmo\Tests\IpTraceable\Fixture\Type;
use Gedmo\Tests\IpTraceable\Fixture\UsingTrait;
use Gedmo\Tests\IpTraceable\Fixture\WithoutInterface;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Translatable\TranslatableListener;

/**
 * Functional tests for the IP traceable extension
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
final class IpTraceableTest extends ORMTestCase
{
    private const TEST_IP = '34.234.1.10';

    private const ARTICLE = Article::class;
    private const COMMENT = Comment::class;
    private const TITLED_ARTICLE = TitledArticle::class;
    private const TYPE = Type::class;
    private const USING_TRAIT = UsingTrait::class;
    private const WITHOUT_INTERFACE = WithoutInterface::class;

    private IpTraceableListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE,
            self::COMMENT,
            self::TITLED_ARTICLE,
            self::TYPE,
            self::USING_TRAIT,
            self::WITHOUT_INTERFACE,
        ]);
    }

    public function testInvalidIpShouldThrowInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new IpTraceableListener())->setIpValue('xx.xxx.xx.xxx');
    }

    public static function dataValidIps(): \Generator
    {
        yield 'IPv4 address' => ['123.218.45.39'];
        yield 'IPv6 address' => ['2001:0db8:0000:85a3:0000:0000:ac1f:8001'];
    }

    /**
     * @dataProvider dataValidIps
     */
    public function testSupportsValidIps(string $ipAddress): void
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue($ipAddress);

        static::assertSame(
            $ipAddress,
            $listener->getFieldValue(
                $this->createStub(ClassMetadata::class),
                'ip',
                $this->createStub(AdapterInterface::class)
            )
        );
    }

    public function testIpTraceable(): void
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        static::assertInstanceOf(IpTraceable::class, $sport);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        static::assertInstanceOf(IpTraceable::class, $sportComment);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);

        static::assertSame(self::TEST_IP, $sport->getCreated());
        static::assertSame(self::TEST_IP, $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);

        static::assertSame(self::TEST_IP, $sportComment->getModified());
        static::assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);

        $published = new Type();
        $published->setTitle('Published');

        $sport->setTitle('Updated');
        $sport->setType($published);

        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);

        static::assertSame(self::TEST_IP, $sportComment->getClosed());
        static::assertSame(self::TEST_IP, $sport->getPublished());
    }

    public function testIpTraceableNoInterface(): void
    {
        $test = new WithoutInterface();
        $test->setTitle('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::WITHOUT_INTERFACE)->findOneBy(['title' => 'Test']);

        static::assertSame(self::TEST_IP, $test->getCreated());
        static::assertSame(self::TEST_IP, $test->getUpdated());
    }

    public function testForcedValues(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_IP);
        $sport->setUpdated(self::TEST_IP);

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);

        static::assertSame(self::TEST_IP, $sport->getCreated());
        static::assertSame(self::TEST_IP, $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_IP);

        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);

        static::assertSame(self::TEST_IP, $sport->getPublished());
    }

    public function testChange(): void
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::TITLED_ARTICLE)->findOneBy(['title' => 'Test']);
        $test->setTitle('New Title');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        // Changed
        static::assertSame(self::TEST_IP, $test->getChtitle());

        $this->listener->setIpValue('127.0.0.1');

        $test = $this->em->getRepository(self::TITLED_ARTICLE)->findOneBy(['title' => 'New Title']);
        $test->setText('New Text');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        // Not Changed
        static::assertSame(self::TEST_IP, $test->getChtitle());
    }

    public function testShouldIpTraceUsingTrait(): void
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedFromIp());
        static::assertNotNull($sport->getUpdatedFromIp());
    }

    public function testTraitMethodsShouldReturnObject(): void
    {
        $sport = new UsingTrait();

        static::assertSame($sport, $sport->setCreatedFromIp(self::TEST_IP));
        static::assertSame($sport, $sport->setUpdatedFromIp(self::TEST_IP));
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale('en_US');

        $this->listener = new IpTraceableListener();
        $this->listener->setIpValue(self::TEST_IP);

        $evm->addEventSubscriber($translatableListener);
        $evm->addEventSubscriber($this->listener);
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

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\IpTraceable\Fixture');
    }
}
