<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
#[ODM\Document(collection: 'articles')]
class Article implements Blameable
{
    /**
     * @ODM\ReferenceOne(targetDocument="Type", inversedBy="articles")
     */
    #[ODM\ReferenceOne(targetDocument: Type::class, inversedBy: 'articles')]
    public ?Type $type = null;

    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    private string $title;

    /**
     * @var Collection<int, Comment>
     *
     * @ODM\ReferenceMany(targetDocument="Comment", mappedBy="article")
     */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'article')]
    private Collection $comments;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Blameable(on="create")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\Blameable(on: 'create')]
    private ?string $created = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Blameable
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\Blameable]
    private ?string $updated = null;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    #[ODM\Field(type: MongoDBType::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'type.title', value: 'Published')]
    private ?string $published = null;

    public function __construct(string $title)
    {
        $this->comments = new ArrayCollection();

        $this->setTitle($title);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @throws \InvalidArgumentException if the title is empty
     */
    public function setTitle(string $title): void
    {
        if ('' === trim($title)) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        $this->title = $title;
    }

    public function addComment(Comment $comment): void
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->article = $this;
        }
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function setCreated(?string $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setUpdated(?string $updated): void
    {
        $this->updated = $updated;
    }

    public function getPublished(): ?string
    {
        return $this->published;
    }

    public function setPublished(?string $published): void
    {
        $this->published = $published;
    }
}
