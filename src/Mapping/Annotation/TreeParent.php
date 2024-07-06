<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

/**
 * TreeParent annotation for Tree behavioral extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class TreeParent implements Annotation
{
}
