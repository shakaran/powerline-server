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
 * @Route("/")
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
            return $this->redirect($this->generateUrl('webuser_login'));
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

    /**
     * @Route("/beta", name="civix_front_user_beta")
     * @Method({"GET"})
     * @Template()
     */
    public function betaAction()
    {
        if (true === $this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('civix_front_user'));
        }

        return array();
    }
	
	/**
     * @Route("/forgot-password", name="civix_front_user_forgot_password")
     */
    public function forgotPassword(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('CivixCoreBundle:User')->findOneBy(array(
            'email' => $request->get('email')
        ));
        if (!$user) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        //check reset expiration
        if (!$this->get('civix_core.user_manager')->checkResetInterval($user)) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' =>
                array(array(
                    'message' => 'The password for this user has already been requested within the last 24 hours.'
                    )
                )))
            );

            return $response;
        }

        //Generate reset token, set date of reset and sent email
        $resetPasswordToken = base_convert(bin2hex(hash('sha256', uniqid(mt_rand(), true), true)), 16, 36);
        $user->setResetPasswordToken($resetPasswordToken);
        $user->setResetPasswordAt(new \DateTime());
        $em->persist($user);
        $em->flush($user);
        
        //send mail
        $this->get('civix_core.email_sender')->sendResetPasswordEmail(
            $user->getEmail(),
            array(
                'name' => $user->getOfficialName(),
                'link' => $this->getWebDomain() . '/#/reset-password/'. $resetPasswordToken
            )
        );
        $response->setContent(json_encode(array('status'=>'ok')))->setStatusCode(200);

        return $response;
    }

    /**
     * @return string
     */
    private function getToken()
    {
        return $this->get('form.csrf_provider')->generateCsrfToken('superuser');
    }

    private function getWebDomain()
    {
        $request = $this->getRequest();
        $host = $request->getHttpHost();

        return $request->getScheme() . '://' .  str_replace('admin.', '', $host);
    }
	
	/*
	 /user/registration
	/user/facebook/login
	/user/registration-facebook
	/user/forgot-password
	/user/resettoken/
	/user/beta
	 */
}
