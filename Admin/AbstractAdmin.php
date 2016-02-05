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
}