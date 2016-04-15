<?php

namespace Xima\CoreBundle\Admin\Helper;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Xima\CoreBundle\Admin\AbstractAdmin;

class TrashAdminHelper {
    
    public static function createQuery($context = 'list', AbstractAdmin $admin)
    {
        $query = $admin->createQuery($context);
        // this is the queryproxy, you can call anything you could call on the doctrine orm QueryBuilder
        $query->andWhere($query->getRootAlias() . '.deletedAt IS NOT NULL');
        if (!parent::isSuperAdmin()) {
            $query->andWhere($query->expr()
                ->eq($query->getRootAlias() . '.username', ':username'));
            $query->setParameter('username', $admin->getConfigurationPool()
                ->getContainer()
                ->get('security.context')
                ->getToken()
                ->getUser()->getUsername());
        }

        return $query;
    }

    // Fields to be shown on filter forms
    public static function configureDatagridFilters(DatagridMapper $datagridMapper, AbstractAdmin $admin)
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
    public static function configureListFields(ListMapper $listMapper, AbstractAdmin $admin)
    {
        $listMapper
            ->addIdentifier('title');
        if ($admin->isSuperAdmin()) {
            $listMapper
                ->add('username');
        }
        $listMapper
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

    public static function configureRoutes(RouteCollection $collection, AbstractAdmin $admin)
    {
        // to remove a single route
        $collection->remove('show');
        $collection->remove('create');
        $collection->add('undelete', '{id}/undelete');
    }
}