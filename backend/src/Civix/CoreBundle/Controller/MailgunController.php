<?php

namespace Civix\CoreBundle\Controller;

use Mailgun\Mailgun;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MailgunController extends Controller
{

    /**
     * @Route("/log" , name="mailgun_test")
     */
    public function logAction()
    {

       return $this->get('civix_core.mailgun')->listremovememberAction('sofiendev','sofien1105@gmail.com');

    }

}

