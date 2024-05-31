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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="without_interfaces")
 */
#[ODM\Document(collection: 'without_interfaces')]
class WithoutInterface
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private string $title;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Blameable(on="create")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Blameable(on: 'create')]
    private ?string $created = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Blameable(on="update")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Blameable(on: 'update')]
    private ?string $updated = null;

    public function __construct(string $title)
    {
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

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }
}
