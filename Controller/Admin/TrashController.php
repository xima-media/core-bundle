<?php

namespace Xima\CoreBundle\Controller\Admin;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xima\CoreBundle\Controller\Admin\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Exception\ModelManagerException;

class TrashController extends CRUDController
{

    public function editAction($id = null)
    {
        $em = $this->get('doctrine')->getManager();

        if ($em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
        }

        return parent::editAction($id);
    }

    public function deleteAction($id)
    {
        $id = $this->get('request')->get($this->admin->getIdParameter());
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

                if (method_exists($this->admin, 'postFlush')) {
                    $this->admin->postFlush();
                }

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

        return $this->render('XimaCoreBundle:Admin:delete.html.twig', array(
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.delete'),
        ));
    }

    /**
     * Undelete action.
     *
     * @param int|string|null $id
     * @todo translation not working
     *
     * @return Response|RedirectResponse
     *
     * @throws NotFoundHttpException If the object does not exist
     * @throws AccessDeniedException If access is not granted
     */
    public function undeleteAction($id)
    {
        $em = $this->get('doctrine')->getManager();
        /* @var $em EntityManagerInterface */

        if ($em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
        }

        $id = $this->get('request')->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if (false === $this->admin->isGranted('EDIT', $object)) {
            throw new AccessDeniedException();
        }

        $this->admin->setSubject($object);

        if ($this->getRestMethod() == 'UNDELETE') {
            // check the csrf token
            $this->validateCsrfToken('sonata.undelete');

            $countErrors = count($this->container->get('validator')->validate($object));

            // persist if there are no validation errors
            if (empty($countErrors)) {
                try {
                    if (method_exists($this->admin, 'undelete')) {
                        $this->admin->undelete($object);
                    } elseif (method_exists($object, 'undelete')) {
                        $object->undelete();
                    }

                    $em = $this->get('doctrine')->getEntityManager();
                    $em->persist($object);
                    $em->flush();

                    if (method_exists($this->admin, 'postFlush')) {
                        $this->admin->postFlush();
                    }

                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson(array('result' => 'ok'));
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->admin->trans(
                            'flash_undelete_success',
                            array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                            'XimaCoreBundle'
                        )
                    );

                } catch (ModelManagerException $e) {
                    $countErrors = 1;
                }
            }

            if (!empty($countErrors)) {

                $constraintList = $this->container->get('validator')->validate($object);
                /* @var $constraintList \Symfony\Component\Validator\ConstraintViolationList */

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'error'));
                }

                foreach ($constraintList as $constraint) {
                    /* @var $constraint \Symfony\Component\Validator\ConstraintViolationInterface */
                    $this->addFlash(
                        'sonata_flash_error',
                        $constraint->getMessage()
                    );
                }
            }

            // on validation errors: head directly to edit action
            $parameters = array();
            $parameters['id'] = $object->getId();

            return new RedirectResponse($this->admin->generateUrl('edit', $parameters));
        }

        return $this->render('XimaCoreBundle:Admin:undelete.html.twig', array(
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.undelete'),
        ));
    }

    /**
     * todo: share delete logic with that of deleteAction
     *
     * @param ProxyQueryInterface $query
     * @return RedirectResponse
     */
    public function batchActionDelete(ProxyQueryInterface $query)
    {
        if (false === $this->admin->isGranted('DELETE')) {
            throw new AccessDeniedException();
        }

        $em = $this->get('doctrine')->getManager();

        if ($em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
        }

        $modelManager = $this->admin->getModelManager();
        try {

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

    public function batchActionUndelete(ProxyQueryInterface $selectedModelQuery)
    {
        if (false === $this->admin->isGranted('DELETE')) {
            throw new AccessDeniedException();
        }

        $em = $this->get('doctrine')->getManager();

        if ($em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
        }

        $selectedModels = $selectedModelQuery->execute();

        try {
            foreach ($selectedModels as $selectedModel) {

                $countErrors = count($this->container->get('validator')->validate($selectedModel));

                // persist if there are no validation errors
                if (empty($countErrors)) {
                    try {
                        if (method_exists($this->admin, 'undelete')) {
                            $this->admin->undelete($selectedModel);
                        } elseif (method_exists($selectedModel, 'undelete')) {
                            $selectedModel->undelete();
                        }

                        $em->persist($selectedModel);

                        if (method_exists($this->admin, 'postFlush')) {
                            $this->admin->postFlush();
                        }

                        $this->addFlash(
                            'sonata_flash_success',
                            $this->admin->trans(
                                'flash_undelete_success',
                                array('%name%' => $this->escapeHtml($this->admin->toString($selectedModel))),
                                'XimaCoreBundle'
                            )
                        );

                    } catch (ModelManagerException $e) {
                        $countErrors = 1;
                        $this->logModelManagerException($e);
                    }
                } else {
                    $this->addFlash(
                        'sonata_flash_success',
                        $this->admin->trans(
                            'flash_undelete_error',
                            array('%name%' => $this->escapeHtml($this->admin->toString($selectedModel))),
                            'XimaCoreBundle'
                        )
                    );
                    $countErrors = 1;
                }
            }

        } catch (\Exception $e) {
            $this->addFlash('sonata_flash_error', 'flash_batch_undelete_error2');

            return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
        }

        if ($countErrors) {
            $this->addFlash('sonata_flash_success', 'flash_batch_undelete_error3');
        } else {
            $this->addFlash('sonata_flash_success', 'flash_undelete_success');
        }

        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
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
