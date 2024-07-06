<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Types\Type;
use Symfony\Bridge\Doctrine\Types\UuidType;

/*
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Christoph Krämer <cevou@gmx.de>
 * @link http://www.gediminasm.org
 */

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', sys_get_temp_dir().'/doctrine-extension-tests');

require dirname(__DIR__).'/vendor/autoload.php';

Type::addType('uuid', UuidType::class);
