<?php
namespace Xima\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Route\RouteCollection;

class AbstractAdmin extends Admin
{
    /**
     * @return boolean is current user super admin
     */
    public function isSuperAdmin()
    {
        return $this->getConfigurationPool()->getContainer()->get('security.context')->isGranted('ROLE_SUPER_ADMIN');
    }
}