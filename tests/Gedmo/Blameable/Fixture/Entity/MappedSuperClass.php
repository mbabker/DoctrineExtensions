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
 * @ORM\MappedSuperclass
 */
#[ORM\MappedSuperclass]
class MappedSuperClass implements Blameable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    protected ?int $id = null;

    /**
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @ORM\Column(name="name", type="string", length=191)
     *
     * @Gedmo\Translatable
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 191)]
    #[Gedmo\Translatable]
    protected string $name;

    /**
     * @ORM\Column(name="created_by", type="string")
     *
     * @Gedmo\Blameable(on="create")
     */
    #[ORM\Column(name: 'created_by', type: Types::STRING)]
    #[Gedmo\Blameable(on: 'create')]
    protected ?string $createdBy = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getId(): ?int
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