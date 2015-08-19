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
    protected $entityManager;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $addCondSql = '';
        if (in_array('Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable', Util::classUsesDeep($targetEntity->getName()))) {
            $conn = $this->getEntityManager()->getConnection();
            $platform = $conn->getDatabasePlatform();
            $column = $targetEntity->getQuotedColumnName('deletedAt', $platform);
            $addCondSql = $platform->getIsNullExpression($targetTableAlias.'.'.$column);
        }

        return $addCondSql;
    }

    protected function getEntityManager()
    {
        if ($this->entityManager === null) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $this->entityManager = $refl->getValue($this);
        }

        return $this->entityManager;
    }
}
