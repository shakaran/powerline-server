<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use Doctrine\ORM\Query;

class BaseController extends Controller
{
    /**
     * @param  Query   $query  Query to get paginated result from
     * @param  array   $groups Serialization groups
     * @param  integer $status HTTP status
     * @param  integer $page   Page
     * @param  integer $limit  How much records per page
     * @return Response
     */
    protected function createPaginatedJSONResponseFromQuery(Query $query, $groups, $status = 200, $page = 1, $limit = 20)
    {
        $paginator = $this->get('knp_paginator');
        $paginatedData = $paginator->paginate($query, $page, $limit);
        return $this->createJSONResponse(
            $this->jmsSerialization($paginatedData, $groups),
            $status
        );
    }

    /**
     * @param  Query   $query  Query to get paginated result from
     * @param  array   $groups Serialization groups
     * @param  integer $status HTTP status
     * @return Response
     */
    protected function createJSONResponseFromQuery(Query $query, $groups, $status = 200)
    {
        $data = $query->getResult();
        return $this->createJSONResponse(
            $this->jmsSerialization($data, $groups),
            $status
        );
    }

    /**
     * @param string  $content JSON string (serialized by JMSSerializer)
     * @param integer $status  HTTP status code
     * @return Response
     */
    protected function createJSONResponse($content = '', $status = 200)
    {
        $response = new Response($content, $status);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @return \Symfony\Component\Validator\Validator
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }

    protected function transformErrors(\Symfony\Component\Validator\ConstraintViolationList $errors)
    {
        $result = array();
        foreach ($errors as $error) {
            /* @var $error \Symfony\Component\Validator\ConstraintViolation */
            $result[] = array(
                'property' => $error->getPropertyPath(),
                'message' => $error->getMessage()
            );
        }

        return $result;
    }

    protected function validate($data, $groups = null)
    {
        $errors = $this->getValidator()->validate($data, $groups);
        if (count($errors) > 0) {
            throw new BadRequestHttpException(json_encode(array('errors' => $this->transformErrors($errors))));
        }
    }

    protected function jmsSerialization($serializationObject, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');
        $serializerContext = SerializationContext::create()->setGroups($groups);

        return $serializer->serialize($serializationObject, $type, $serializerContext);
    }

    protected function jmsDeserialization($content, $class, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');
        $serializerContext = DeserializationContext::create()->setGroups($groups);

        return $serializer->deserialize($content, $class, $type, $serializerContext);
    }

    protected function getJson()
    {
        return json_decode($this->getRequest()->getContent());
    }
}
