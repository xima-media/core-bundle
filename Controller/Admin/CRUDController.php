<?php

namespace Xima\CoreBundle\Controller\Admin;

class CRUDController extends \Sonata\AdminBundle\Controller\CRUDController
{
    /**
     * {@inheritDoc} Also allows editing of an object even if it is marked as delete.
     */
    public function editAction($id = null)
    {
        if ($this->isSuperAdmin()) {
            $this->get('doctrine')->getManager()->getFilters()->disable('soft_deleteable');
        }

        $id = $this->get('request')->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        return parent::editAction($id);
    }

    /**
     * {@inheritDoc} Also lists objects even if the are marked as delete.
     */
    public function listAction()
    {
        if ($this->isSuperAdmin()) {
            $this->get('doctrine')->getManager()->getFilters()->disable('soft_deleteable');
        }

        return parent::listAction();
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
        if (!$this->isSuperAdmin()) {
            throw new AccessDeniedException();
        }

        $this->get('doctrine')->getManager()->getFilters()->disable('soft_deleteable');

        $id = $this->get('request')->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if ($this->getRestMethod() == 'UNDELETE') {
            // check the csrf token
            $this->validateCsrfToken('sonata.undelete');

            try {
                if (method_exists($this->admin, 'undelete')) {
                    $this->admin->undelete();
                } elseif (method_exists($object, 'undelete')) {
                    $object->undelete();
                }

                $em = $this->get('doctrine')->getEntityManager();
                $em->persist($object);
                $em->flush();

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'ok'));
                }

                $this->addFlash(
                    'sonata_flash_success',
                    $this->admin->trans(
                        'flash_undelete_success',
                        array('%name%' => $this->escapeHtml($this->admin->toString($object)))
                    )
                );
            } catch (ModelManagerException $e) {
                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'error'));
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $this->admin->trans(
                        'flash_undelete_error',
                        array('%name%' => $this->escapeHtml($this->admin->toString($object)))
                    )
                );
            }

            return $this->redirectTo($object);
        }

        return $this->render('XimaCoreBundle:Admin:undelete.html.twig', array(
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.undelete'),
        ));
    }

    /**
     * @return bool is current user super admin
     */
    protected function isSuperAdmin()
    {
        return $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN');
    }
}
