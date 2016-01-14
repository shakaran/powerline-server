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

    /**
     * @Route("/login", name="civix_front_user_login")
     * @Method({"GET"})
     */
    public function loginAction()
    {
        $csrfToken = $this->container->get('form.csrf_provider')->generateCsrfToken('user_authentication');

        if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
            $this->get('request')->getSession()->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render('CivixFrontBundle:User:login.html.twig', array(
                'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
                'error' => $error,
                'csrf_token' => $csrfToken
            ));
    }
}
