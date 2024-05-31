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

/**
 * @ODM\Document(collection="editorials")
 */
#[ODM\Document(collection: 'editorials')]
class Editorial extends MappedSuperClass
{
    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private string $title;

    public function __construct(string $name, string $title)
    {
        parent::__construct($name);

        $this->setTitle($title);
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
