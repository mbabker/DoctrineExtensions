<?php

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use Gedmo\Tests\Mapping\Fixture\Annotation\Referenced;
use Gedmo\Tests\Mapping\Fixture\Annotation\Referencer;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for ReferenceIntegrity extension
 *
 * @author Jonathan Eskew <jonathan@jeskew.net>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ReferenceIntegrityMappingTest extends BaseTestCaseOM
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ReferenceIntegrityListener
     */
    private $referenceIntegrity;

    protected function setUp(): void
    {
        parent::setUp();

        $driver = new AnnotationDriver($_ENV['annotation_reader'], __DIR__.'/Driver/Annotation');

        $this->referenceIntegrity = new ReferenceIntegrityListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->referenceIntegrity);

        $this->dm = $this->getMockDocumentManager('gedmo_extensions_test', $driver);
    }

    public function testMapping()
    {
        $referencerMeta = $this->dm->getClassMetadata(Referencer::class);
        $referencedMeta = $this->dm->getClassMetadata(Referenced::class);
        $config = $this->referenceIntegrity->getConfiguration($this->dm, $referencerMeta->name);

        static::assertNotEmpty($config['referenceIntegrity']);
        foreach ($config['referenceIntegrity'] as $propertyName => $referenceConfiguration) {
            static::assertArrayHasKey($propertyName, $referencerMeta->reflFields);

            foreach ($referenceConfiguration as $inversedPropertyName => $integrityType) {
                static::assertArrayHasKey($inversedPropertyName, $referencedMeta->reflFields);
                static::assertContains($integrityType, ['nullify', 'restrict']);
            }
        }
    }
}
