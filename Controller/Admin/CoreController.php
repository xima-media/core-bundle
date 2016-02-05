<?php

namespace Xima\CoreBundle\Controller\Admin;

class CoreController extends \Sonata\AdminBundle\Controller\CoreController
{

    public function dashboardAction()
    {
        if ($this->isSuperAdmin()) {
            $this->get('doctrine')->getManager()->getFilters()->disable('soft_deleteable');
        }

        return parent::dashboardAction();
    }

    /**
     * @return bool is current user super admin
     */
    protected function isSuperAdmin()
    {
        return $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN');
    }
}
