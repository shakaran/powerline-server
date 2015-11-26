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
            return new JsonResponse('Listname invalid or already exist',200);
        }
        $result = $mailgun->post("lists", array(
            'address'     => $listname.'@powerlinegroups.com',
            'description' => ''.$description,
            'access_level' => 'members'
        ));

        if($result['http_response_code'] != 200){

            $this->listaddmemberAction($listname.'@powerlinegroups.com',$email,$name);

        }

        return json_decode(json_encode($result),true);

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
            $result = $this->listcreateAction($listname,' the list '.$listname);

            if($result['http_response_code'] != 200){

                return json_decode(json_encode($result),true);

            }
        }
            $result = $mailgun->post("lists/$listAddress/members", array(
                'address'     => ''.$address,
                'name'        => ''.$name,
                'subscribed'  => true,
                'upsert'      => true
            ));

        return json_decode(json_encode($result),true);

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
            $result = $this->listcreateAction($listname,' the list '.$listname);

            if($result['http_response_code'] != 200){

                return json_decode(json_encode($result),true);

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
            $result = $this->listcreateAction($listname,'new list '.$listname);
        }


        return json_decode(json_encode($result),true);

    }

    public function listremoveAction($listname)
    {

        $mailgun = new Mailgun($this->private_key,"api.mailgun.net","v3",true);

        $listAddress = $listname.'@powerlinegroups.com';

        $result = $mailgun->delete("lists/$listAddress");


        return json_decode(json_encode($result),true);

    }

}
