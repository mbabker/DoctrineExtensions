<?php

namespace Gedmo\SoftDeleteable;

/**
 * Optional interface which can be used to identify
 * SoftDeleteable objects.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SoftDeleteable
{
    // this interface is not necessary to implement

    /*
     * @gedmo:SoftDeleteable
     * to mark the class as SoftDeleteable use class annotation @gedmo:SoftDeleteable
     * this object will be able to be soft deleted
     * example:
     *
     * @gedmo:SoftDeleteable
     * class MyEntity
     */
}
