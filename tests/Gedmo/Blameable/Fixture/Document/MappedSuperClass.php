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
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\MappedSuperclass
 */
#[ODM\MappedSuperclass]
class MappedSuperClass implements Blameable
{
    /**
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Translatable
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Translatable]
    protected string $name;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="create")
     */
    #[ODM\Field(type: Type::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'create')]
    protected ?string $createdBy = null;
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws \InvalidArgumentException if the name is empty
     */
    public function setName(string $name): void
    {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        $this->name = $name;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }
}
