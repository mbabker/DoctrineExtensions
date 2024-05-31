<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosureWithoutMapping;

/**
 * @ORM\Entity
 * @ORM\Table(name="closure_categories", indexes={@ORM\Index(name="search_idx", columns={"title"})})
 * @Gedmo\Tree(type="nested")
 * @Gedmo\TreeClosure(class="Gedmo\Tests\Tree\Fixture\Closure\CategoryClosureWithoutMapping")
 */
#[ORM\Entity]
#[ORM\Table(name: 'closure_categories')]
#[ORM\Index(name: 'search_idx', columns: ['title'])]
#[Gedmo\Tree(type: 'closure')]
#[Gedmo\TreeClosure(class: CategoryClosureWithoutMapping::class)]
class ClosureCategory
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
     */
    #[ORM\Column(type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\Mapping\Fixture\ClosureCategory", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Mapping\Fixture\ClosureCategory", inversedBy="children")
     *
     * @Gedmo\TreeParent
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Gedmo\TreeParent]
    private ?ClosureCategory $parent = null;

    /**
     * @ORM\Column(type="integer")
     *
     * @Gedmo\TreeLevel
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private ?int $level = null;

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

    public function addChildren(self $children): void
    {
        $this->children[] = $children;
    }

    /**
     * @return Collection<int, self>
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

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }
}
