<?php

namespace Xima\CoreBundle\Controller\Admin;

use Xima\CoreBundle\Controller\Admin\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Exception\ModelManagerException;

class TrashController extends CRUDController {

    public function deleteAction($id)
    {
        $id     = $this->get('request')->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('DELETE', $object)) {
            throw new AccessDeniedException();
        }

        if ($this->getRestMethod() == 'DELETE') {
            // check the csrf token
            $this->validateCsrfToken('sonata.delete');

            try {
                $em = $this->getDoctrine()->getManager();

                // initiate an array for the removed listeners
                $originalEventListeners = array();

                // cycle through all registered event listeners
                foreach ($em->getEventManager()->getListeners() as $eventName => $listeners) {
                    foreach ($listeners as $listener) {
                        if ($listener instanceof \Knp\DoctrineBehaviors\ORM\SoftDeletable\SoftDeletableSubscriber) {

                            // store the event listener, that gets removed
                            $originalEventListeners[$eventName] = $listener;

                            // remove the SoftDeletableSubscriber event listener
                            $em->getEventManager()->removeEventListener($eventName, $listener);
                        }
                    }
                }

                // remove the entity
                $em->remove($object);
                $em->flush();

                // re-add the removed listener back to the event-manager
                foreach ($originalEventListeners as $eventName => $listener) {
                    $em->getEventManager()->addEventListener($eventName, $listener);
                }

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'ok'));
                }

                $this->addFlash(
                    'sonata_flash_success',
                    $this->admin->trans(
                        'flash_delete_success',
                        array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                        'SonataAdminBundle'
                    )
                );
            } catch (ModelManagerException $e) {
                $this->logModelManagerException($e);

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'error'));
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $this->admin->trans(
                        'flash_delete_error',
                        array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                        'SonataAdminBundle'
                    )
                );
            }

            return new RedirectResponse($this->admin->generateUrl(
                'list',
                array('filter' => $this->admin->getFilterParameters())
            ));
        }

        return $this->render($this->admin->getTemplate('delete'), array(
            'object'     => $object,
            'action'     => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.delete'),
        ));
    }

    public function batchActionDelete(ProxyQueryInterface $query)
    {
        if (false === $this->admin->isGranted('DELETE')) {
            throw new AccessDeniedException();
        }

        $modelManager = $this->admin->getModelManager();
        try {
            $em = $this->getDoctrine()->getManager();

            // initiate an array for the removed listeners
            $originalEventListeners = array();

            // cycle through all registered event listeners
            foreach ($em->getEventManager()->getListeners() as $eventName => $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof \Knp\DoctrineBehaviors\ORM\SoftDeletable\SoftDeletableSubscriber) {

                        // store the event listener, that gets removed
                        $originalEventListeners[$eventName] = $listener;

                        // remove the SoftDeletableSubscriber event listener
                        $em->getEventManager()->removeEventListener($eventName, $listener);
                    }
                }
            }

            // remove the entity
            $modelManager->batchDelete($this->admin->getClass(), $query);
            // re-add the removed listener back to the event-manager
            foreach ($originalEventListeners as $eventName => $listener) {
                $em->getEventManager()->addEventListener($eventName, $listener);
            }
            $this->addFlash('sonata_flash_success', 'flash_batch_delete_success');
        } catch (ModelManagerException $e) {
            $this->logModelManagerException($e);
            $this->addFlash('sonata_flash_error', 'flash_batch_delete_error');
        }

        return new RedirectResponse($this->admin->generateUrl(
            'list',
            array('filter' => $this->admin->getFilterParameters())
        ));
    }

    private function logModelManagerException($e)
    {
        $context = array('exception' => $e);
        if ($e->getPrevious()) {
            $context['previous_exception_message'] = $e->getPrevious()->getMessage();
        }
        $this->getLogger()->error($e->getMessage(), $context);
    }

}