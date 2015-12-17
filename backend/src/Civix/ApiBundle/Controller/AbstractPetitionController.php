<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Poll\Option;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/petition")
 */
abstract class AbstractPetitionController extends AbstractOwnerController
{
    /**
     * @todo Sortable implementation
     * 
     * @Route(
     *      "/{ownerId}/petition",
     *      name="api_petition_list",
     *      requirements={
     *          "ownerId" = "\d+"
     *      }
     * )
     * @Method("GET")
     * @ApiDoc(
     *      resource=true,
     *      description="List announcements",
     *      filters={
     *         {"name"="filter", "dataType"="string",  "requirement"="published|new"},
     *         {"name"="page",   "dataType"="integer", "requirement"="\d+"},
     *         {"name"="sort",   "dataType"="string",  "requirement"="[a-z_]+", "description"="Sorting field name"},
     *         {"name"="dir",    "dataType"="string",  "requirement"="asc|desc", "description"="Sorting direction"}
     *      }
     * )
     */
    public function listAction(Request $request, $ownerId)
    {
        $owner = $this->getOwner($ownerId);

        // if (!$this->isGranred('PETITION_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $repository = $this->getRepository();

        switch ($request->query->get('filter')) {
            case 'new':
                $query = $repository->getNewPetitionsQuery($owner);
                break;

            case 'published':
                $query = $repository->getPublishedPetitionsQuery($owner);
                break;

            default:
                $query = $repository->getAllPetitionsQuery($owner);
        }

        return $this->createPaginatedJSONResponseFromQuery(
            $query, 
            array(
                'api',
                'api-list'
            ), 
            200, 
            $request->query->getInt('page', 1)
        );
    }

    /**
     * @Route(
     *      "/{ownerId}/petition/{id}",
     *      name="api_petition_get",
     *      requirements={
     *          "ownerId" = "\d+",
     *          "id" = "\d+"
     *      }
     * )
     * @Method("GET")
     * @ParamConverter("petition", class="CivixCoreBundle:Poll\Question\Petition")
     */
    public function getAction(Request $request, $ownerId, Petition $petition)
    {
        $owner = $this->getOwner($ownerId);

        if ($petition->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('PETITION_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        return $this->createJSONResponse($this->jmsSerialization($petition, array(
            'api',
            'api-get'
        )), 200);
    }

    /**
     * Create action
     * 
     * @Route(
     *      "/{ownerId}/petition",
     *      name="api_petition_create",
     *      requirements={
     *          "ownerId" = "\d+"
     *      }
     * )
     * @Method("POST")
     * @ApiDoc(
     *      statusCodes={
     *          201="Announcement created",
     *          400="Bad Request",
     *          403="Access Denied"
     *      }
     * )
     */
    public function createAction(Request $request, $ownerId)
    {
        $owner = $this->getOwner($ownerId);

        if ($petition->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('PETITION_CREATE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $petition = $this->jmsDeserialization(
            $request->getContent(), $this->getEntity(), ['api', 'api-create']
        );

        // @todo EducationalContext
        // @todo Option

        $petition
            ->setUser($owner)
        ;

        $errors = $this->getValidator()->validate($petition, ['api', 'api-create']);
        if (count($errors) > 0) {
            return $this->createJSONResponse(json_encode([
                'errors' => $this->transformErrors($errors)
            ]), 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($petition);
        $manager->flush($petition);
        
        return $this->createJSONResponse(
            $this->jmsSerialization($petition, ['api', 'api-create']),
            201
        );
    }

    /**
     * @Route(
     *     "/{ownerId}/petition/{id}",
     *     name="api_petition_update",
     *      requirements={
     *          "ownerId" = "\d+",
     *          "id" = "\d+"
     *      }
     * )
     * @Method("PUT")
     * @ParamConverter("petition", class="CivixCoreBundle:Poll\Question\Petition")
     * @ApiDoc(
     *      statusCodes={
     *          204="Petition updated"
     *      }
     * )
     */
    public function updateAction(Request $request, $ownerId, Petition $petition)
    {
        $owner = $this->getOwner($ownerId);

        if ($petition->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('PETITION_UPDATE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($petition->getPublishedAt()) {
            throw new BadRequestHttpException("You can't update published petitions");   
        }

        $petition = $this->jmsDeserialization(
            $request->getContent(), $this->getEntity(), ['api']
        );

        // @todo EducationalContext
        // @todo Option

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($petition);
        $manager->flush();

        return $this->createJSONResponse('[]', 204);    
    }

    /**
     * @Route(
     *     "/{ownerId}/petition/{id}",
     *     name="api_petition_delete",
     *      requirements={
     *          "ownerId" = "\d+",
     *          "id" = "\d+"
     *      }
     * )
     * @Method("DELETE")
     * @ParamConverter("petition", class="CivixCoreBundle:Poll\Question\Petition")
     * @ApiDoc(
     *      statusCodes={
     *          204="Success"
     *      }
     * )
     */
    public function deleteAction(Request $request, $ownerId, Petition $petition)
    {
        $owner = $this->getOwner($ownerId);

        if ($petition->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('PETITION_DELETE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($petition->getPublishedAt()) {
            throw new BadRequestHttpException("You can't delete published announcements");   
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($petition);
        $manager->flush();

        return $this->createJSONResponse('[]', 204);
    }

    /**
     * @Route(
     *     "/{ownerId}/petition/{id}/publish",
     *      name="api_petition_publish",
     *      requirements={
     *          "ownerId" = "\d+",
     *          "id" = "\d+"
     *      }
     * )
     * @Method("PATCH")
     * @ParamConverter("petition", class="CivixCoreBundle:Poll\Question\Petition")
     * @ApiDoc(
     *      statusCodes={
     *          204="Success"
     *      }
     * )
     */
    public function publishAction(Request $request, $ownerId, Petition $petition)
    {
        $owner = $this->getOwner($ownerId);

        if ($petition->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        if ($petition->getPublishedAt()) {
            throw new BadRequestHttpException("This petition already published");
        }

        // if (!$this->isGranred('PETITION_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $petition->setPublishedAt(new \DateTime());

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($petition);
        $manager->flush($petition);

        $this->getDoctrine()
            ->getRepository('CivixCoreBundle:HashTag')
            ->addForQuestion($petition)
        ;

        $this->get('civix_core.activity_update')
            ->publishPetitionToActivity($petition)
        ;

        return $this->createJSONResponse('[]', 204);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('CivixCoreBundle:Poll\Question');
    }

    abstract protected function getEntity();
}
