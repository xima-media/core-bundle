<?php

namespace Xima\CoreBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class CRUDController extends \Sonata\AdminBundle\Controller\CRUDController
{
    /**
     * The related Admin class.
     *
     * @var \Xima\CoreBundle\Admin\AbstractAdmin
     */
    protected $admin;

    /**
     * {@inheritDoc} Also allows editing of an object even if it is marked as delete.
     */
    public function editAction($id = null)
    {
        if ($this->admin->isSuperAdmin()) {
            $em = $this->get('doctrine')->getManager();
            /* @var $em EntityManagerInterface */

            if ($em->getFilters()->isEnabled('softdeleteable')) {
                $em->getFilters()->disable('softdeleteable');
            }
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
        if ($this->admin->isSuperAdmin()) {
            $em = $this->get('doctrine')->getManager();
            /* @var $em EntityManagerInterface */

            if ($em->getFilters()->isEnabled('softdeleteable')) {
                $em->getFilters()->disable('softdeleteable');
            }
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
        if (!$this->admin->isSuperAdmin()) {
            throw new AccessDeniedException();
        }

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

        if ($this->getRestMethod() == 'UNDELETE') {
            // check the csrf token
            $this->validateCsrfToken('sonata.undelete');

            try {
                if (method_exists($this->admin, 'undelete')) {
                    $this->admin->undelete($object);
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
                        array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                        'XimaCoreBundle'
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
                        array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                        'XimaCoreBundle'
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
}
