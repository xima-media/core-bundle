<?php

namespace Xima\CoreBundle\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Xima\CoreBundle\Helper\Util;

/**
 * The SoftDeleteableFilter for knplabs/doctrine-behaviors adds the condition necessary to * filter entities which were deleted "softly".
 *
 * @see https://github.com/l3pp4rd/DoctrineExtensions/blob/master/lib/Gedmo/SoftDeleteable/Filter/SoftDeleteableFilter.php
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 * @author Wolfram Eberius <edrush@posteo.de>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $constraint = '';
        if (in_array('Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable', Util::classUsesDeep($targetEntity->getName()))) {
            $constraint = $targetTableAlias . '.deletedAt is null';
        }

        return $constraint;
    }
}
