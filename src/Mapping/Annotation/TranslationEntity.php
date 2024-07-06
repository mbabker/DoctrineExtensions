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
 * TranslationEntity annotation for Translatable behavioral extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class TranslationEntity implements Annotation
{
    use ForwardCompatibilityTrait;

    /** @Required */
    public string $class;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [], string $class = '')
    {
        if ([] !== $data) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2253',
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            );

            $args = func_get_args();

            $this->class = $this->getAttributeValue($data, 'class', $args, 1, $class);

            return;
        }

        $this->class = $class;
    }
}
