<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Annotation;

use Gedmo\Mapping\Annotation\Annotation;
use PHPUnit\Framework\TestCase;

abstract class BasePropertyAnnotationTestCase extends TestCase
{
    /**
     * @dataProvider getValidParameters
     *
     * @param mixed $expectedReturn
     */
    public function testLoadFromAttribute(string $annotationProperty, string $classProperty, $expectedReturn): void
    {
        $annotation = $this->getMethodAnnotation($classProperty);
        static::assertSame($annotation->$annotationProperty, $expectedReturn);
    }

    /**
     * @phpstan-return iterable<int, array{0: string, 1: string, 2: ?bool}>
     */
    abstract public function getValidParameters(): iterable;

    abstract protected function getAnnotationClass(): string;

    abstract protected function getAttributeModelClass(): string;

    private function getMethodAnnotation(string $property): Annotation
    {
        $class = $this->getAttributeModelClass();
        $reflection = new \ReflectionProperty($class, $property);
        $annotationClass = $this->getAnnotationClass();

        $attributes = $reflection->getAttributes($annotationClass);
        $annotation = $attributes[0]->newInstance();

        if (!is_a($annotation, $annotationClass)) {
            throw new \LogicException('Can\'t parse annotation.');
        }

        return $annotation;
    }
}
