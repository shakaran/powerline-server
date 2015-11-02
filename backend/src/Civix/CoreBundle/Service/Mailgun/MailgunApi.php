<?php
/**
 * Created by PhpStorm.
 * User: sofien
 * Date: 11/10/15
 * Time: 11:50
 */

namespace Civix\CoreBundle\Service\Mailgun;


use Symfony\Component\HttpFoundation\JsonResponse;
use Mailgun\Mailgun;

class MailgunApi {

    static $public_key;
    static $private_key;

    function __construct($public_key,$private_key)
    {
        $this::$public_key = $public_key;
        $this::$private_key = $private_key;
    }


    public function listcreateAction($listname,$description)
    {

        $mailgun = new Mailgun($this::$private_key,"api.mailgun.net","v3",true);
        $publicmailgun = new Mailgun($this::$public_key,"api.mailgun.net","v3",true);

        $validation = $publicmailgun->get("address/validate", array('address' => $listname.'@powerlinegroups.com'));
        $validationresponse = json_decode(json_encode($validation),true);

        if($validationresponse['http_response_code'] == 200 AND $validationresponse['http_response_body']['is_valid'] === false){
            return new JsonResponse('Listname invalid or already exist',200);
        }
        $result = $mailgun->post("lists", array(
            'address'     => $listname.'@powerlinegroups.com',
            'description' => ''.$description,
            'access_level' => 'members'
        ));

        return new JsonResponse($result);

    }

    public function listaddmemberAction($listname,$address,$name)
    {

        $mailgun = new Mailgun($this::$private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';

        $result = $mailgun->post("lists/$listAddress/members", array(
            'address'     => ''.$address,
            'name'        => ''.$name,
            'subscribed'  => true,
            'upsert'      => true
        ));


        return new JsonResponse($result);

    }

    public function listremovememberAction($listname,$address)
    {

        $mailgun = new Mailgun($this::$private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';
        $listMember = ''.$address;

        $result = $mailgun->delete("lists/$listAddress/members/$listMember");


        return new JsonResponse($result);

    }

    public function listremoveAction($listname)
    {

        $mailgun = new Mailgun($this::$private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';

        $result = $mailgun->delete("lists/$listAddress");


        return new JsonResponse($result);

    }

}
