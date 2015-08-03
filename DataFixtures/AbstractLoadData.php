<?php
namespace Xima\CoreBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Xima\CoreBundle\Entity\AbstractEntity;
use Doctrine\Common\Persistence\ObjectManager;

abstract class AbstractLoadData extends AbstractFixture
{

    public function loadEntity(AbstractEntity $entity, ObjectManager $manager)
    {
        if ($entity->getId()) {
            // Explicity set ID
            $metadata = $manager->getClassMetaData(get_class($entity));
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        }
        
        if (isset($manager)) {
            $manager->persist($entity);
            $manager->flush();
        }
        
        $this->addReference(get_class($entity) . $entity->getId(), $entity);
    }
}