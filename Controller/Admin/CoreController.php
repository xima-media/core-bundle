<?php

namespace Xima\CoreBundle\Controller\Admin;

class CoreController extends \Sonata\AdminBundle\Controller\CoreController
{

    public function dashboardAction()
    {
        $em = $this->get('doctrine')->getManager();

        if ($this->isSuperAdmin() && $em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
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
