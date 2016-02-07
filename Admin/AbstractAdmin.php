<?php
namespace Xima\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Route\RouteCollection;

class AbstractAdmin extends Admin
{
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('undelete', '{id}/undelete');
    }

    /**
     * @return boolean is current user super admin
     */
    protected function isSuperAdmin()
    {
        return $this->getConfigurationPool()->getContainer()->get('security.context')->isGranted('ROLE_SUPER_ADMIN');
    }
}