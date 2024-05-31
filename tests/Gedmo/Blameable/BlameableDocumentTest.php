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
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Document\Article;
use Gedmo\Tests\Blameable\Fixture\Document\Comment;
use Gedmo\Tests\Blameable\Fixture\Document\Editorial;
use Gedmo\Tests\Blameable\Fixture\Document\Page;
use Gedmo\Tests\Blameable\Fixture\Document\Post;
use Gedmo\Tests\Blameable\Fixture\Document\Type;
use Gedmo\Tests\Blameable\Fixture\Document\User;
use Gedmo\Tests\Blameable\Fixture\Document\UsingTrait;
use Gedmo\Tests\Blameable\Fixture\Document\WithoutInterface;
use Gedmo\Tests\MongoDBODMTestCase;
use Gedmo\Translatable\Document\Repository\TranslationRepository;
use Gedmo\Translatable\Document\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * Functional tests for the blameable extension when integrated with the Doctrine MongoDB ODM.
 * *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @requires extension mongodb
 */
final class BlameableDocumentTest extends MongoDBODMTestCase
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

    public function testSetsBlameableUserInformationOnABlameableDocumentUsingAStringField(): void
    {
        $sport = new Article('Sport');

        $sportComment = new Comment($sport, 'hello');
        $sportComment->setStatus(0);

        $this->dm->persist($sport);
        $this->dm->persist($sportComment);
        $this->dm->flush();

        static::assertSame(self::BLAMEABLE_USER_NAME, $sport->getCreated(), 'The blameable user should be set when a document is created to the correct field.');
        static::assertSame(self::BLAMEABLE_USER_NAME, $sport->getUpdated(), 'The blameable user should be set when a document is updated to the correct field.');
        static::assertNull($sport->getPublished(), 'The blameable user should not be set when a document is created and the field should only be updated for configured changes.');

        static::assertSame(self::BLAMEABLE_USER_NAME, $sportComment->getModified(), 'The blameable user should be set when a document is updated to the correct field.');
        static::assertNull($sportComment->getClosed(), 'The blameable user should not be set when a document is created and the field should only be updated for configured changes.');

        $sportComment->setStatus(Comment::STATUS_CLOSED);

        $published = new Type('Published');

        $sport->setTitle('Updated');
        $sport->type = $published;

        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->persist($sportComment);
        $this->dm->flush();

        static::assertSame(self::BLAMEABLE_USER_NAME, $sport->getPublished(), 'The blameable user should be set when a document is updated and the field should be updated for configured changes.');

        static::assertSame(self::BLAMEABLE_USER_NAME, $sportComment->getClosed(), 'The blameable user should be set when a document is updated and the field should be updated for configured changes.');
    }

    public function testDoesNotOverwriteUserProvidedValuesForBlameableFields(): void
    {
        $sport = new Article('sport forced');
        $sport->setCreated('myuser');
        $sport->setUpdated('myuser');

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        static::assertSame('myuser', $sport->getCreated());
        static::assertSame('myuser', $sport->getUpdated());

        $published = new Type('Published');

        $sport->type = $published;
        $sport->setPublished('myuser');

        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        static::assertSame('myuser', $sport->getPublished());
    }

    public function testSupportsBlameableOnMappedSuperclass(): void
    {
        $editorial = new Editorial('name', 'title');

        $this->dm->persist($editorial);
        $this->dm->flush();
        $this->dm->clear();

        static::assertSame(self::BLAMEABLE_USER_NAME, $editorial->getCreatedBy());

        /** @var TranslationRepository $repo */
        $repo = $this->dm->getRepository(self::TRANSLATION);

        static::assertCount(0, $repo->findTranslations($editorial));
    }

    public function testSetsBlameableInformationWhenUsingTheTrait(): void
    {
        $document = new UsingTrait('Sport');

        $this->dm->persist($document);
        $this->dm->flush();

        static::assertNotNull($document->getCreatedBy());
        static::assertNotNull($document->getUpdatedBy());
    }

    public function testTheBlameableTraitProvidesAFluentInterface(): void
    {
        $document = new UsingTrait('Sport');

        static::assertSame($document, $document->setCreatedBy('myuser'));
        static::assertSame($document, $document->setUpdatedBy('myuser'));
    }

    public function testDoesNotRequireDocumentsToImplementTheBlameableInterface(): void
    {
        $test = new WithoutInterface('Test');

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        static::assertSame(self::BLAMEABLE_USER_NAME, $test->getCreated());
        static::assertSame(self::BLAMEABLE_USER_NAME, $test->getUpdated());
    }

    public function testSetsBlameableUserInformationOnABlameableDocumentUsingARelation(): void
    {
        $user = new User('Test User');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->listener->setUserValue($user);

        $post = new Post('Post Title');

        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        static::assertSame($user, $post->getCreatedBy());
    }

    public function testSetsBlameableUserInformationOnABlameableDocumentWhenAObjectIsSetToTheListenerAndTheFieldExpectsAString(): void
    {
        $user = new User('Test User');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->listener->setUserValue($user);

        $article = new Article('Article Title');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        static::assertSame($user->getUsername(), $article->getCreated());
    }

    public function testDoesNotFailWhenThereIsNoUser(): void
    {
        $this->listener->setUserValue(null);

        $page = new Page('page no user');

        $this->dm->persist($page);
        $this->dm->flush();
        $this->dm->clear();

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
        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            $annotationOrAttributeDriver = $this->createAttributeDriver([__DIR__.'/Fixture/Document', __DIR__.'/../../../src/Translatable/Document']);
        } elseif (class_exists(AnnotationDriver::class)) {
            $annotationOrAttributeDriver = $this->createAnnotationDriver([__DIR__.'/Fixture/Document', __DIR__.'/../../../src/Translatable/Document']);
        } else {
            static::markTestSkipped('Test requires PHP 8 with doctrine/mongodb-odm attribute support or doctrine/mongodb-odm with annotations support.');
        }

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Blameable\Fixture\Document');
        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Translatable\Document');
    }
}
