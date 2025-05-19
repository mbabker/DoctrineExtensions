<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver as MongoDBODMAttributeDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver as ORMAttributeDriver;
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
    public const VERSION = '3.20.0';

    /**
     * Hooks all extension metadata mapping drivers into
     * the given driver chain of drivers for the ORM.
     */
    public static function registerMappingIntoDriverChainORM(MappingDriverChain $driverChain): void
    {
        $driverChain->addDriver(
            new ORMAttributeDriver([
                __DIR__.'/Translatable/Entity',
                __DIR__.'/Loggable/Entity',
                __DIR__.'/Tree/Entity',
            ]),
            'Gedmo'
        );
    }

    /**
     * Hooks only superclass extension metadata mapping drivers into
     * the given driver chain of drivers for the ORM.
     */
    public static function registerAbstractMappingIntoDriverChainORM(MappingDriverChain $driverChain): void
    {
        $driverChain->addDriver(
            new ORMAttributeDriver([
                __DIR__.'/Translatable/Entity/MappedSuperclass',
                __DIR__.'/Loggable/Entity/MappedSuperclass',
                __DIR__.'/Tree/Entity/MappedSuperclass',
            ]),
            'Gedmo'
        );
    }

    /**
     * Hooks all extension metadata mapping drivers into
     * the given driver chain of drivers for the MongoDB ODM.
     */
    public static function registerMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain): void
    {
        $driverChain->addDriver(
            new MongoDBODMAttributeDriver([
                __DIR__.'/Translatable/Document',
                __DIR__.'/Loggable/Document',
                __DIR__.'/Tree/Document',
            ]),
            'Gedmo'
        );
    }

    /**
     * Hooks only superclass extension metadata mapping drivers into
     * the given driver chain of drivers for the MongoDB ODM.
     */
    public static function registerAbstractMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain): void
    {
        $driverChain->addDriver(
            new MongoDBODMAttributeDriver([
                __DIR__.'/Translatable/Document/MappedSuperclass',
                __DIR__.'/Loggable/Document/MappedSuperclass',
            ]),
            'Gedmo'
        );
    }
}
