<?php
namespace Xima\CoreBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

interface InitializableControllerInterface
{
    public function initialize(Request $request, SecurityContextInterface $security_context);
}
?>