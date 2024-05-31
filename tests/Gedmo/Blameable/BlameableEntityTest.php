<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\Article;
use Gedmo\Tests\Blameable\Fixture\Entity\Comment;
use Gedmo\Tests\Blameable\Fixture\Entity\Editorial;
use Gedmo\Tests\Blameable\Fixture\Entity\Page;
use Gedmo\Tests\Blameable\Fixture\Entity\Post;
use Gedmo\Tests\Blameable\Fixture\Entity\Type;
use Gedmo\Tests\Blameable\Fixture\Entity\User;
use Gedmo\Tests\Blameable\Fixture\Entity\UsingTrait;
use Gedmo\Tests\Blameable\Fixture\Entity\WithoutInterface;
use Gedmo\Tests\ORMTestCase;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * Functional tests for the blameable extension when integrated with the Doctrine ORM.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class BlameableEntityTest extends ORMTestCase
{
    private const ARTICLE = Article::class;
    private const COMMENT = Comment::class;
    private const EDITORIAL = Editorial::class;
    private const PAGE = Page::class;
    private const POST = Post::class;
    private const TRANSLATION = Translation::class;
    private const TYPE = Type::class;
    private const USER = User::class;
    private const USING_TRAIT = UsingTrait::class;
    private const WITHOUT_INTERFACE = WithoutInterface::class;

    private const BLAMEABLE_USER_NAME = 'testuser';

    private BlameableListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForObjects([
            self::ARTICLE,
            self::COMMENT,
            self::EDITORIAL,
            self::PAGE,
            self::POST,
            self::TRANSLATION,
            self::TYPE,
            self::USER,
            self::USING_TRAIT,
            self::WITHOUT_INTERFACE,
        ]);
    }

    public function testSetsBlameableUserInformationOnABlameableEntityUsingAStringField(): void
    {
        $sport = new Article('Sport');

        $sportComment = new Comment($sport, 'hello');
        $sportComment->setStatus(0);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();

        static::assertSame(self::BLAMEABLE_USER_NAME, $sport->getCreated(), 'The blameable user should be set when an entity is created to the correct field.');
        static::assertSame(self::BLAMEABLE_USER_NAME, $sport->getUpdated(), 'The blameable user should be set when an entity is updated to the correct field.');
        static::assertNull($sport->getPublished(), 'The blameable user should not be set when an entity is created and the field should only be updated for configured changes.');

        static::assertSame(self::BLAMEABLE_USER_NAME, $sportComment->getModified(), 'The blameable user should be set when an entity is updated to the correct field.');
        static::assertNull($sportComment->getClosed(), 'The blameable user should not be set when an entity is created and the field should only be updated for configured changes.');

        $sportComment->setStatus(Comment::STATUS_CLOSED);

        $published = new Type('Published');

        $sport->setTitle('Updated');
        $sport->type = $published;

        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        static::assertSame(self::BLAMEABLE_USER_NAME, $sport->getPublished(), 'The blameable user should be set when an entity is updated and the field should be updated for configured changes.');

        static::assertSame(self::BLAMEABLE_USER_NAME, $sportComment->getClosed(), 'The blameable user should be set when an entity is updated and the field should be updated for configured changes.');
    }

    public function testDoesNotOverwriteUserProvidedValuesForBlameableFields(): void
    {
        $sport = new Article('sport forced');
        $sport->setCreated('myuser');
        $sport->setUpdated('myuser');

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('myuser', $sport->getCreated());
        static::assertSame('myuser', $sport->getUpdated());

        $published = new Type('Published');

        $sport->type = $published;
        $sport->setPublished('myuser');

        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('myuser', $sport->getPublished());
    }

    public function testSupportsBlameableOnMappedSuperclass(): void
    {
        $editorial = new Editorial('name', 'title');

        $this->em->persist($editorial);
        $this->em->flush();
        $this->em->clear();

        static::assertSame(self::BLAMEABLE_USER_NAME, $editorial->getCreatedBy());

        /** @var TranslationRepository $repo */
        $repo = $this->em->getRepository(self::TRANSLATION);

        static::assertCount(0, $repo->findTranslations($editorial));
    }

    public function testSetsBlameableInformationWhenUsingTheTrait(): void
    {
        $object = new UsingTrait('Sport');

        $this->em->persist($object);
        $this->em->flush();

        static::assertNotNull($object->getCreatedBy());
        static::assertNotNull($object->getUpdatedBy());
    }

    public function testTheBlameableTraitProvidesAFluentInterface(): void
    {
        $object = new UsingTrait('Sport');

        static::assertSame($object, $object->setCreatedBy('myuser'));
        static::assertSame($object, $object->setUpdatedBy('myuser'));
    }

    public function testDoesNotRequireEntitiesToImplementTheBlameableInterface(): void
    {
        $test = new WithoutInterface('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        static::assertSame(self::BLAMEABLE_USER_NAME, $test->getCreated());
        static::assertSame(self::BLAMEABLE_USER_NAME, $test->getUpdated());
    }

    public function testSetsBlameableUserInformationOnABlameableEntityUsingARelation(): void
    {
        $user = new User('Test User');

        $this->em->persist($user);
        $this->em->flush();

        $this->listener->setUserValue($user);

        $post = new Post('Post Title');

        $this->em->persist($post);
        $this->em->flush();
        $this->em->clear();

        static::assertSame($user, $post->getCreatedBy());
    }

    public function testSetsBlameableUserInformationOnABlameableEntityWhenAObjectIsSetToTheListenerAndTheFieldExpectsAString(): void
    {
        $user = new User('Test User');

        $this->em->persist($user);
        $this->em->flush();

        $this->listener->setUserValue($user);

        $article = new Article('Article Title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        static::assertSame($user->getUsername(), $article->getCreated());
    }

    public function testDoesNotFailWhenThereIsNoUser(): void
    {
        $this->listener->setUserValue(null);

        $page = new Page('page no user');

        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        static::assertNull($page->getCreated());
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale('en_US');

        $this->listener = new BlameableListener();
        $this->listener->setUserValue(self::BLAMEABLE_USER_NAME);

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

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Blameable\Fixture\Entity');
        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Translatable\Entity');
    }
}
