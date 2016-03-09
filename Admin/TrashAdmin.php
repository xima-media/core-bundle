<?php

namespace Xima\CoreBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Routing\Router;
use Xima\XRBSBundle\Security\AccessManager;

class TrashAdmin extends AbstractAdmin {

    protected $baseRouteName = 'trash';
    protected $baseRoutePattern = 'trash';

    public function createQuery($context = 'list')
    {
        $query = parent::createQuery($context);
        // this is the queryproxy, you can call anything you could call on the doctrine orm QueryBuilder
        $query->andWhere($query->getRootAlias() . '.deletedAt IS NOT NULL');
        if (!parent::isSuperAdmin()) {
            $query->andWhere($query->expr()
                ->eq($query->getRootAlias() . '.username', ':username'));
            $query->setParameter('username', $this->getConfigurationPool()
                ->getContainer()
                ->get('security.context')
                ->getToken()
                ->getUser()->getUsername());
        }

        return $query;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('bookingType')
        ;
    }

    /**
     * Fields to be shown on lists.
     *
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $em = $this->getConfigurationPool()
            ->getContainer()
            ->get('doctrine')
            ->getEntityManager();

        $listMapper
            ->add('title', 'html', array(
                'truncate' => array(
                    'length' => 30
                )))
            ->add('username')
            ->add('bookingType')
            ->add('deletedAt', null, array('format' => 'j M Y, g:i a'))
            ->add('_action', 'actions',
                array(
                    'actions' => array(
                        'undelete' => array('template' => 'XimaCoreBundle:Admin:list__action_undelete.html.twig'),
                        'delete' => array('template' => 'XimaCoreBundle:Admin:list__action_delete.html.twig'),
                    ),
                ));
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        // to remove a single route
        $collection->remove('show');
        $collection->remove('create');

        parent::configureRoutes($collection);
    }

}