<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Comment implements Blameable
{
    public const STATUS_OPEN = 0;
    public const STATUS_CLOSED = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Blameable\Fixture\Entity\Article", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    public Article $article;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @ORM\Column(name="message", type="text")
     */
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private string $message;

    /**
     * @phpstan-var self::STATUS_*
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $status = self::STATUS_OPEN;

    /**
     * @ORM\Column(name="closed", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="change", field="status", value=1)
     */
    #[ORM\Column(name: 'closed', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'status', value: self::STATUS_CLOSED)]
    private ?string $closed = null;

    /**
     * @ORM\Column(name="modified", type="string")
     *
     * @Gedmo\Blameable(on="update")
     */
    #[ORM\Column(name: 'modified', type: Types::STRING)]
    #[Gedmo\Blameable(on: 'update')]
    private ?string $modified = null;

    public function __construct(Article $article, string $message)
    {
        $this->article = $article;

        $this->setMessage($message);
    }

    public function getId(): ?int
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
