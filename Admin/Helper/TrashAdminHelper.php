<?php

namespace Xima\CoreBundle\Admin\Helper;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Xima\CoreBundle\Admin\AbstractAdmin;

class TrashAdminHelper
{

    public static function filterQuery($query)
    {
        $query->where($query->getRootAlias() . '.deletedAt IS NOT NULL');
    }

    /**
     * Fields to be shown on lists.
     *
     * @param ListMapper $listMapper
     */
    public static function configureListFields(ListMapper $listMapper, AbstractAdmin $admin)
    {
        $listMapper
            ->add('_action', 'actions',
                array(
                    'actions' => array(
                        'undelete' => array('template' => 'XimaCoreBundle:Admin:list__action_undelete.html.twig'),
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

    public static function getBatchActions(AbstractAdmin $admin)
    {
        $actions = array();

        // check user permissions
        if ($admin->hasRoute('delete') && $admin->isGranted('DELETE')) {
            $actions['undelete'] = [
                'label' => $admin->trans('action_undelete', array(), 'XimaCoreBundle'),
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            ];
        }

        return $actions;
    }
}