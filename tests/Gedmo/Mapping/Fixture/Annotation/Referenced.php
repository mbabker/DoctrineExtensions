<?php

namespace Gedmo\Tests\Mapping\Fixture\Annotation;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="test_referenced")
 */
class Referenced
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Mapping\Fixture\Annotation\Referencer", inversedBy="referencedDocuments")
     */
    private $referencer;
}
