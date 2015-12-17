<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractNewsController extends BaseController
{
    /**
     * @Route(
     *     "/{ownerId}/news",
     *     name="api_news_list",
     *     requirements={
     *         "ownerId" = "\d+"
     *     }
     * )
     * @Method("GET")
     * @ApiDoc(
     *      resource=true,
     *      filters={
     *         {"name"="filter", "dataType"="string",  "requirement"="published|new"},
     *         {"name"="page",   "dataType"="integer", "requirement"="\d+"},
     *         {"name"="sort",   "dataType"="string",  "requirement"="[a-z_]+", "description"="Sorting field name"},
     *         {"name"="dir",    "dataType"="string",  "requirement"="asc|desc", "description"="Sorting direction"}
     *      },
     *      statusCodes={
     *          200="Returns announcements"
     *      }
     * )
     */
    public function listAction(Request $request, $ownerId)
    {
        $filter = $request->query->get('filter');
        $owner = $this->getOwner($ownerId);

        // if (!$this->isGranred('NEWS_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        /* @var $repository \Civix\CoreBundle\Repository\Poll\QuestionRepository */
        $repository = $this->getRepository();

        switch ($filter) {
            case 'new':
                $query = $repository->getNewLeaderNewsQuery($owner);
                break;

            case 'published':
                $query = $repository->getPublishedLeaderNewsQuery($owner);
                break;

            default:
                // Show all news by default
                $query = $repository->getAllLeaderNewsQuery($owner);
        }

        return $this->createPaginatedJSONResponseFromQuery(
            $query, 
            array('api', 'api-list'), 
            200, 
            $request->query->getInt('page', 1)
        );
    }

    /**
     * @Route(
     *      "/{ownerId}/news/{id}",
     *      name="api_news_get",
     *      requirements={
     *         "ownerId" = "\d+",
     *          "id" = "\d+"
     *      },
     * )
     * @Method("GET")
     * @ParamConverter("new", class="CivixCoreBundle:Poll\Question")
     */
    public function getAction(Request $request, $ownerId, Question $new)
    {
        $owner = $this->getOwner($ownerId);

        if ($new->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('NEWS_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        return $this->createJSONResponse($this->jmsSerialization($new, ['api', 'api-get']));
    }

    /**
     * @Route(
     *      "/{ownerId}/news",
     *      name="api_news_create",
     *      requirements={
     *         "ownerId" = "\d+"
     *      },
     * )
     * @Method("POST")
     * @ApiDoc(
     *      statusCodes={
     *          201="New created"
     *      }
     * )
     */
    public function createAction(Request $request, $ownerId)
    {
        $owner = $this->getOwner($ownerId);

        if ($new->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('NEWS_CREATE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $new = $this->jmsDeserialization(
            $request->getContent(), $this->getNewsClass(), ['api', 'api-create']
        );

        $new
            ->setUser($this->getUser())
        ;

        // @todo EducationalContext

        $errors = $this->getValidator()->validate($new, ['api', 'api-create']);
        if (count($errors) > 0) {
            return $this->createJSONResponse(json_encode([
                'errors' => $this->transformErrors($errors)
            ]), 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($new);
        $manager->flush($new);
        
        return $this->createJSONResponse($this->jmsSerialization($new, ['api', 'api-create']), 201);
    }

    /**
     * @Route(
     *     "/{ownerId}/news/{id}",
     *     name="api_news_update",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("PUT")
     * @ParamConverter("new", class="CivixCoreBundle:Poll\Question")
     * @ApiDoc(
     *      statusCodes={
     *          204="Announcement updated"
     *      }
     * )
     */
    public function updateAction(Request $request, $ownerId, Question $new)
    {
        $owner = $this->getOwner($ownerId);

        if ($new->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('NEWS_UPDATE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($new->getPublishedAt()) {
            throw new BadRequestHttpException("You can't update published news");   
        }

        $newUpdated = $this->jmsDeserialization(
            $request->getContent(), $this->getNewsClass(), ['api', 'api-update']
        );

        // @todo NewsManager implementation
        $this->get('civix_core.news_manager')->update($new, $newUpdated);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($new);
        $manager->flush();

        // return $this->createJSONResponse('[]', 204);
        return $this->createJSONResponse(
            $this->jmsSerialization($new, array('api')),
            200
        );
    }

    /**
     * @Route(
     *     "/{ownerId}/news/{id}",
     *     name="api_news_delete",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("DELETE")
     * @ParamConverter("new", class="CivixCoreBundle:Poll\Question")
     * @ApiDoc(
     *      statusCodes={
     *          204="Success"
     *      }
     * )
     */
    public function deleteAction(Request $request, $ownerId, Question $new)
    {
        $owner = $this->getOwner($ownerId);

        if ($new->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('NEWS_DELETE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($new->getPublishedAt()) {
            throw new BadRequestHttpException("You can't delete published news");   
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($new);
        $manager->flush();

        return $this->createJSONResponse('[]', 204);
    }

    /**
     * @Route(
     *     "/{ownerId}/news/{id}/publish",
     *     name="api_news_publish",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("PATCH")
     * @ParamConverter("new", class="CivixCoreBundle:Poll\Question")
     * @ApiDoc(
     *      statusCodes={
     *          204="Success"
     *      }
     * )
     */
    public function publishAction(Request $request, $ownerId, Question $new)
    {
        $owner = $this->getOwner($ownerId);

        if ($owner !== $new->getUser()) {
            throw new BadRequestHttpException();
        }

        if ($new->getPublishedAt()) {
            throw new BadRequestHttpException("This news already published");
        }

        // if (!$this->isGranred('NEWS_PUBLISH', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $new->setPublishedAt(new \DateTime());
        
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($new);
        $manager->flush($new);

        $this->get('civix_core.activity_update')->publishLeaderNewsToActivity($new);

        return $this->createJSONResponse('[]', 204);
    }

    /**
     * @param Group|Representative $owner
     * @return bool
     */
    protected function isAvailableGroupSection($owner)
    {
        $packLimitState = $this->get('civix_core.package_handler')
            ->getPackageStateForGroupDivisions($owner);

        return $packLimitState->isAllowed();
    }

    /**
     * @param $news
     * @return bool
     */
    protected function isShowGroupSections($news)
    {
        return ($news instanceof GroupSectionInterface) && $this->isAvailableGroupSection();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(
            Question::class
        );
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getOwnerRepository()
    {
        return $this->getDoctrine()->getRepository(
            $this->getOwnerEntity()
        );
    }

    /**
     * @param integer $ownerId
     *
     * @return Group|Representative
     * @throws \Exception
     */
    protected function getOwner($ownerId)
    {
        $owner = $this->getOwnerRepository()->find($ownerId);

        if (is_subclass_of($owner, $this->getOwnerEntity())) {
            return $owner;
        }

        throw new BadRequestHttpException();
    }

    /**
     * @return string
     */
    abstract protected function getOwnerEntity();

}
