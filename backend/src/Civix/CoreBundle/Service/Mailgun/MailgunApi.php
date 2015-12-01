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

    public $public_key;
    public $private_key;

    function __construct($public_key,$private_key)
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
    }


    public function listcreateAction($listname,$description,$email,$name)
    {

        $mailgun = new Mailgun($this->private_key,"api.mailgun.net","v3",true);
        $publicmailgun = new Mailgun($this->public_key,"api.mailgun.net","v3",true);

        $validation = $publicmailgun->get("address/validate", array('address' => $listname.'@powerlinegroups.com'));
        $validationresponse = json_decode(json_encode($validation),true);

        if($validationresponse['http_response_code'] == 200 AND $validationresponse['http_response_body']['is_valid'] === false){
            return $validationresponse;
        }
        $result = $mailgun->post("lists", array(
            'address'     => $listname.'@powerlinegroups.com',
            'description' => ''.$description,
            'access_level' => 'members'
        ));

        $result = $this->JsonResponse($result);

        if($result['http_response_code'] != 200){

            $this->listaddmemberAction($listname.'@powerlinegroups.com',$email,$name);

        }

        return $result;

    }

    public function listaddmemberAction($listname,$address,$name)
    {

        $mailgun = new Mailgun($this->private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';

        $checkresult = $mailgun->get("lists", array(
            'address'     => ''.$listAddress,
        ));
        $decodedresult = json_decode(json_encode($checkresult),true);
        $count = $decodedresult['http_response_body']['total_count'];

        if($count == 0){
            $result = $this->listcreateAction($listname,' the list '.$listname,$address,$name);

            if($result['http_response_code'] != 200){

                return $this->JsonResponse($result);

            }
        }
            $result = $mailgun->post("lists/$listAddress/members", array(
                'address'     => ''.$address,
                'name'        => ''.$name,
                'subscribed'  => true,
                'upsert'      => true
            ));

        return $this->JsonResponse($result);

    }

    public function listremovememberAction($listname,$address)
    {

        $mailgun = new Mailgun($this->private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';
        $listMember = ''.$address;

        $checkresult = $mailgun->get("lists", array(
            'address'     => ''.$listAddress,
        ));
        $decodedresult = json_decode(json_encode($checkresult),true);
        $count = $decodedresult['http_response_body']['total_count'];

        if($count == 0){
            $result = $this->listcreateAction($listname,' the list '.$listname,$address,' ');

            if($result['http_response_code'] != 200){

                return $this->JsonResponse($result);

            }
        }

        $checkadress = $mailgun->get("lists/$listAddress/members", array(
            'address'     => ''.$address,
        ));

        $decodedresult = json_decode(json_encode($checkadress),true);
        $count = $decodedresult['http_response_body']['total_count'];

        if($count > 0){
            $result = $mailgun->delete("lists/$listAddress/members/$listMember");
        }else{
            $result = $this->listcreateAction($listname,'new list '.$listname,$address,' ');
        }


        return $this->JsonResponse($result);

    }

    public function listremoveAction($listname)
    {

        $mailgun = new Mailgun($this->private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';

        $result = $mailgun->delete("lists/$listAddress");


        return $this->JsonResponse($result);

    }

    public function JsonResponse($result){

        return json_decode(json_encode($result),true);
    }

}
