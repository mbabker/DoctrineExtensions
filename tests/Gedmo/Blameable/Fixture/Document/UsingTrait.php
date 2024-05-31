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
use Gedmo\Blameable\Traits\BlameableDocument;

/**
 * @ODM\Document(collection="using_traits")
 */
#[ODM\Document(collection: 'using_traits')]
class UsingTrait implements Blameable
{
    use BlameableDocument;

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
}
