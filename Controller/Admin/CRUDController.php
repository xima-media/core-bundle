<?php

namespace Xima\CoreBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;


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
        $id = $this->get('request')->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        return parent::editAction($id);
    }

    /**
     * Delete action.
     *
     * @param int|string|null $id
     *
     * @return Response|RedirectResponse
     *
     * @throws NotFoundHttpException If the object does not exist
     * @throws AccessDeniedException If access is not granted
     */
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
                $this->admin->delete($object);

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(array('result' => 'ok'));
                }

                $this->addFlash(
                    'sonata_flash_success',
                    $this->admin->trans(
                        'flash_movetotrash_success',
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
                        'flash_movetotrash_error',
                        array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                        'XimaCoreBundle'
                    )
                );
            }

            return $this->redirectTo($object);
        }

        return $this->render('XimaCoreBundle:Admin:movetotrash.html.twig', array(
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.delete'),
        ));
    }
}
