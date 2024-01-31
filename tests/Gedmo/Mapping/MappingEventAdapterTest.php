<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Gedmo\Mapping\Event\Adapter\ORM as EventAdapterORM;
use Gedmo\Tests\Mapping\Mock\EventSubscriberCustomMock;
use Gedmo\Tests\Mapping\Mock\EventSubscriberMock;
use Gedmo\Tests\Mapping\Mock\Mapping\Event\Adapter\ORM as CustomizedORMAdapter;
use PHPUnit\Framework\TestCase;

final class MappingEventAdapterTest extends TestCase
{
    public function testCustomizedAdapter(): void
    {
        $subscriber = new EventSubscriberCustomMock();
        $args = new PrePersistEventArgs(new \stdClass(), $this->createStub(EntityManagerInterface::class));

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(CustomizedORMAdapter::class, $adapter);
    }

    public function testCorrectAdapter(): void
    {
        $emMock = $this->createStub(EntityManagerInterface::class);
        $subscriber = new EventSubscriberMock();
        $args = new PrePersistEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);

        try {
            $adapter->setEventState($args, $args->getObject());

            static::assertInstanceOf(EventAdapterORM::class, $adapter);
            static::assertSame($adapter->getObjectManager(), $emMock);
            static::assertInstanceOf(\stdClass::class, $adapter->getObject());
        } finally {
            $adapter->clearEventState();
        }
    }

    public function testAdapterBehavior(): void
    {
        $emMock = $this->createStub(EntityManagerInterface::class);
        $args = new PrePersistEventArgs(new \stdClass(), $emMock);

        $eventAdapter = new EventAdapterORM($emMock);

        try {
            $eventAdapter->setEventState($args, $args->getObject());

            static::assertSame($eventAdapter->getObjectManager(), $emMock);
            static::assertInstanceOf(\stdClass::class, $eventAdapter->getObject());
        } finally {
            $eventAdapter->clearEventState();
        }
    }
}
