<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Deprecations\Deprecation;

/**
 * TreeRoot annotation for Tree behavioral extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class TreeRoot implements Annotation
{
    use ForwardCompatibilityTrait;

    /** @var string|null */
    public $identifierMethod;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [], ?string $identifierMethod = null)
    {
        if ([] !== $data) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2388',
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            );

            $args = func_get_args();

            $this->identifierMethod = $this->getAttributeValue($data, 'identifierMethod', $args, 1, $identifierMethod);

            return;
        }

        $this->identifierMethod = $identifierMethod;
    }
}
