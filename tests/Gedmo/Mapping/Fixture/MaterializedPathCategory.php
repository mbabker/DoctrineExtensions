<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fixture;

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="materialized_path_categories", indexes={@ORM\Index(name="search_idx", columns={"title"})})
 * @Gedmo\Tree(type="materializedPath", activateLocking=true)
 */
#[ORM\Entity]
#[ORM\Table(name: 'materialized_path_categories')]
#[ORM\Index(name: 'search_idx', columns: ['title'])]
#[Gedmo\Tree(type: 'materializedPath', activateLocking: true)]
class MaterializedPathCategory
{
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
     * @ORM\Column(type="string", length=64)
     * @Gedmo\TreePathSource
     */
    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Gedmo\TreePathSource]
    private ?string $title = null;

    /**
     * @ORM\Column(type="string", length=3000)
     * @Gedmo\TreePath
     */
    #[ORM\Column(type: Types::STRING, length: 3000)]
    #[Gedmo\TreePath]
    private ?string $path = null;

    /**
     * @ORM\Column(type="integer")
     *
     * @Gedmo\TreeLevel
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private ?int $level = null;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\Mapping\Fixture\MaterializedPathCategory", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Mapping\Fixture\MaterializedPathCategory", inversedBy="children")
     *
     * @Gedmo\TreeParent
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Gedmo\TreeParent]
    private ?MaterializedPathCategory $parent = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\TreeLockTime
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\TreeLockTime]
    private ?\DateTime $lockTime = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function addChildren(Category $children): void
    {
        $this->children[] = $children;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): self
    {
        return $this->parent;
    }

    public function setLevel(?int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setLockTime(?\DateTime $lockTime): void
    {
        $this->lockTime = $lockTime;
    }

    public function getLockTime(): ?\DateTime
    {
        return $this->lockTime;
    }
}
