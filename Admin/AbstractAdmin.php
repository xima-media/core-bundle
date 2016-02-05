<?php
namespace Xima\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Route\RouteCollection;

class AbstractAdmin extends Admin
{
    /**
     * Workaround until SonataAdminBundle 2.4 is out.
     *
     * @see https://github.com/sonata-project/SonataAdminBundle/issues/2493
     * @see https://github.com/sonata-project/SonataAdminBundle/issues/3560
     *
     * {@inheritdoc}
     */
    public function generateObjectUrl($name, $object, array $parameters = array(), $absolute = false)
    {
        $url = '';
        if ('undelete' == $name) {
            $url = $this->getUrlsafeIdentifier($object) . '/undelete';
        } else {
            $url = parent::generateObjectUrl($name, $object, $parameters, $absolute);
        }

        return $url;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('undelete', '{id}/undelete');
    }
}