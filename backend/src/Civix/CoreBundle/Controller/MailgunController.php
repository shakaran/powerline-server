<?php

namespace Civix\CoreBundle\Controller;

use Cocur\Slugify\Slugify;
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
        $slugify = new Slugify();

        $groupName = $slugify->slugify('We Love The Wales');
        //var_dump($groupName);die();

       return $this->get('civix_core.mailgun')->listaddmemberAction('sofiendev','sofien1105@gmail.com','sofien');

    }

}

