<?php

namespace Civix\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;

/**
 * User controller (frontend)
 *
 * @Route("/users")
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="civix_front_user")
     * @Method({"GET"})
     * @Template()
     */
    public function indexAction()
    {
        if (true === $this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('civix_front_user_approvals'));
        }

        return array();
    }

}
