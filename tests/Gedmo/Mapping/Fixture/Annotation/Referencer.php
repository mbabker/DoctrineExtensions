<?php

namespace Gedmo\Tests\Mapping\Fixture\Annotation;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="test_referencers")
 */
class Referencer
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\ReferenceMany(targetDocument="Gedmo\Tests\Mapping\Fixture\Annotation\Referenced", mappedBy="referencer")
     * @Gedmo\ReferenceIntegrity("nullify")
     */
    private $referencedDocuments;
}
