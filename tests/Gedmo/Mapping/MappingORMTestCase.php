<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\ORMTestCase;

abstract class MappingORMTestCase extends ORMTestCase
{
    protected function addMetadataDriversToChain(MappingDriverChain $driver): void
    {
        $driver->addDriver($this->createXmlDriver(__DIR__.'/Driver/Xml'), 'Gedmo\Tests\Mapping\Fixture\Xml');

        if (class_exists(YamlDriver::class)) {
            $driver->addDriver($this->createYamlDriver(__DIR__.'/Driver/Yaml'), 'Gedmo\Tests\Mapping\Fixture\Yaml');
        }

        if (PHP_VERSION_ID >= 80000) {
            $driver->addDriver($this->createAttributeDriver(), 'Gedmo\Tests\Mapping\Fixture');
        } elseif (class_exists(AnnotationDriver::class)) {
            $driver->addDriver($this->createAnnotationDriver(), 'Gedmo\Tests\Mapping\Fixture');
        }
    }
}
