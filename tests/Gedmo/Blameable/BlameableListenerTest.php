<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;
use Gedmo\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BlameableListenerTest extends TestCase
{
    private BlameableListener $listener;

    protected function setUp(): void
    {
        $this->listener = new BlameableListener();
    }

    public function testHasNoUserValueByDefaultForANonAssociationField(): void
    {
        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(false);

        static::assertNull($this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class)));
    }

    public function testHasNoUserValueByDefaultForAnAssociationField(): void
    {
        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(true);

        static::assertNull($this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class)));
    }

    public function testSupportsAnObjectForAnAssociationField(): void
    {
        $this->listener->setUserValue($user = new StringableUser());

        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(true);

        static::assertSame($user, $this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class)));
    }

    public function testRaisesAnErrorWhenAScalarValueIsSetForAnAssociationField(): void
    {
        $this->listener->setUserValue('user');

        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Blame is reference, user must be an object');

        $this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class));
    }

    public function testSupportsAnObjectProvidingAUserIdentifierForAScalarField(): void
    {
        $this->listener->setUserValue($user = new UserIdentifierUser());

        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(false);

        static::assertSame($user->getUserIdentifier(), $this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class)));
    }

    public function testSupportsAnObjectProvidingAUsernameForAScalarField(): void
    {
        $this->listener->setUserValue($user = new UsernameUser());

        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(false);

        static::assertSame($user->getUsername(), $this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class)));
    }

    public function testSupportsAStringableObjectForAScalarField(): void
    {
        $this->listener->setUserValue($user = new StringableUser());

        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(false);

        static::assertSame((string) $user, $this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class)));
    }

    public function testRaisesAnErrorWhenAnUnsupportedObjectIsTheUserWhenTheBlameableUsesAScalarField(): void
    {
        $this->listener->setUserValue(new InvalidUser());

        /** @var MockObject&ClassMetadata $meta */
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::once())
            ->method('hasAssociation')
            ->with('created')
            ->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field expects string, user must be a string, or object should have method getUserIdentifier, getUsername or __toString');

        $this->listener->getFieldValue($meta, 'created', $this->createMock(BlameableAdapter::class));
    }
}

final class InvalidUser
{
}

final class StringableUser
{
    public function __toString(): string
    {
        return 'stringable user';
    }
}

final class UsernameUser
{
    public function getUsername(): string
    {
        return 'username';
    }
}

final class UserIdentifierUser
{
    public function getUserIdentifier(): string
    {
        return 'user identifier';
    }
}
