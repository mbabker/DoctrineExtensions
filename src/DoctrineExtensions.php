<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo;

use Doctrine\ODM\MongoDB\Mapping\Driver as DriverMongodbODM;
use Doctrine\ORM\Mapping\Driver as DriverORM;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;

/**
 * Version class allows checking the required dependencies
 * and the current version of the Doctrine Extensions library.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class DoctrineExtensions
{
    /**
     * Current version of extensions
     */
    public const VERSION = '3.16.1';

    /**
     * Hooks all extension metadata mapping drivers into
     * the given driver chain of drivers for the ORM.
     */
    public static function registerMappingIntoDriverChainORM(MappingDriverChain $driverChain): void
    {
        $paths = [
            __DIR__.'/Translatable/Entity',
            __DIR__.'/Loggable/Entity',
            __DIR__.'/Tree/Entity',
        ];

        $driverChain->addDriver(new DriverORM\AttributeDriver($paths), 'Gedmo');
    }

    /**
     * Hooks only superclass extension metadata mapping drivers into
     * the given driver chain of drivers for the ORM.
     */
    public static function registerAbstractMappingIntoDriverChainORM(MappingDriverChain $driverChain): void
    {
        $paths = [
            __DIR__.'/Translatable/Entity/MappedSuperclass',
            __DIR__.'/Loggable/Entity/MappedSuperclass',
            __DIR__.'/Tree/Entity/MappedSuperclass',
        ];

        $driverChain->addDriver(new DriverORM\AttributeDriver($paths), 'Gedmo');
    }

    /**
     * Hooks all extension metadata mapping drivers into
     * the given driver chain of drivers for the MongoDB ODM.
     */
    public static function registerMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain): void
    {
        $paths = [
            __DIR__.'/Translatable/Document',
            __DIR__.'/Loggable/Document',
        ];

        $driverChain->addDriver(new DriverMongodbODM\AttributeDriver($paths), 'Gedmo');
    }

    /**
     * Hooks only superclass extension metadata mapping drivers into
     * the given driver chain of drivers for the MongoDB ODM.
     */
    public static function registerAbstractMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain): void
    {
        $paths = [
            __DIR__.'/Translatable/Document/MappedSuperclass',
            __DIR__.'/Loggable/Document/MappedSuperclass',
        ];

        $driverChain->addDriver(new DriverMongodbODM\AttributeDriver($paths), 'Gedmo');
    }
}
