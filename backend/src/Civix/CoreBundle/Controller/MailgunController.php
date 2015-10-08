<?php

namespace Civix\CoreBundle\Controller;

use Mailgun\Mailgun;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AccountController extends Controller
{

    /**
     * @Route("/test/mailgun", name="mailgun_test_log")
     */
    public function logAction()
    {
        $mailgun = new Mailgun('099047fdcf1cc1a813df
602a6f72ef8b','v3',false);

        $domain = "powerlinegroups.com";

        return $mailgun->get("$domain/log", array('limit' => 25,
            'skip'  => 0));
    }


}

