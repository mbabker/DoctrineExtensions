<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="comments")
 */
#[ODM\Document(collection: 'comments')]
class Comment implements Blameable
{
    public const STATUS_OPEN = 0;
    public const STATUS_CLOSED = 1;

    /**
     * @ODM\ReferenceOne(targetDocument="Article", inversedBy="comments")
     */
    #[ODM\ReferenceOne(targetDocument: Article::class, inversedBy: 'comments')]
    public Article $article;

    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    private string $message;

    /**
     * @phpstan-var self::STATUS_*
     *
     * @ODM\Field(type="int")
     */
    #[ODM\Field(type: MongoDBType::INT)]
    private int $status = self::STATUS_OPEN;

    /**
     * @ODM\Field(type="string")
     *
     * @@Gedmo\Blameable(on="change", field="status", value=1)
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\Blameable(on: 'change', field: 'status', value: self::STATUS_CLOSED)]
    private ?string $closed = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Blameable(on="update")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\Blameable(on: 'update')]
    private ?string $modified = null;

    public function __construct(Article $article, string $message)
    {
        $this->article = $article;

        $this->setMessage($message);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @throws \InvalidArgumentException if the message is empty
     */
    public function setMessage(string $message): void
    {
        if ('' === trim($message)) {
            throw new \InvalidArgumentException('Message cannot be empty');
        }

        $this->message = $message;
    }

    /**
     * @phpstan-return self::STATUS_*
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @phpstan-param self::STATUS_* $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getModified(): ?string
    {
        return $this->modified;
    }

    public function getClosed(): ?string
    {
        return $this->closed;
    }
}
