<?php

namespace Gedmo\ReferenceIntegrity;

/**
 * Optional interface which can be used to identify
 * objects which have ReferenceIntegrity checked.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface ReferenceIntegrity
{
    /*
     * ReferenceIntegrity expects certain settings to be required
     * in combination with an association
     */

    /*
     * example
     * @ODM\ReferenceOne(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("nullify")
     * @var Article
     */

    /*
     * example
     * @ODM\ReferenceOne(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("restrict")
     * @var Article
     */

    /*
     * example
     * @ODM\ReferenceMany(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("nullify")
     * @var Doctrine\Common\Collections\ArrayCollection
     */

    /*
     * example
     * @ODM\ReferenceMany(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("restrict")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
}
