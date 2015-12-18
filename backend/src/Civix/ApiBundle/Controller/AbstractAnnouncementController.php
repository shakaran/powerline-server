<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Civix\CoreBundle\Entity\Announcement;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractAnnouncementController extends AbstractOwnerController
{
    /**
     * List announcements.
     *
     * @todo Sortable implementation
     * 
     * @Route(
     *      "/{ownerId}/announcement",
     *      name="api_announcement_list",
     *      requirements={
     *          "ownerId" = "\d+"
     *      }
     * )
     * @Method("GET")
     * @ApiDoc(
     *      resource=true,
     *      description="List announcements.",
     *      filters={
     *         {"name"="filter", "dataType"="string",  "requirement"="published|new"},
     *         {"name"="page",   "dataType"="integer", "requirement"="\d+"},
     *         {"name"="sort",   "dataType"="string",  "requirement"="[a-z_]+", "description"="Sorting field name"},
     *         {"name"="dir",    "dataType"="string",  "requirement"="asc|desc", "description"="Sorting direction"}
     *      },
     *      statusCodes={
     *          200="Returns announcements",
     *          400="Bad Request",
     *          403="Access Denied",
     *      }
     * )
     */
    public function listByOwnerAction(Request $request, $ownerId)
    {
        $filter = $request->query->get('filter');
        $owner = $this->getOwner($ownerId);

        // if (!$this->isGranred('ANNOUNCEMENT_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $repository = $this->getRepository();
        switch ($filter) {
            case 'new':
                $query = $repository->getNewQuery($owner);
                break;

            case 'published':
                $query = $repository->getPublishedQuery($owner);
                break;

            default:
                // Show all announcements by default
                $query = $repository->getAllQuery($owner);
        }

        return $this->createPaginatedJSONResponseFromQuery(
            $query,
            array('api'),
            200,
            $request->query->getInt('page', 1)
        );
    }

    /**
     * Returns announcement info by ID.
     *
     * @Route(
     *      "/{ownerId}/announcement/{id}",
     *      name="api_announcement_get",
     *      requirements={
     *          "ownerId" = "\d+",
     *          "id" = "\d+"
     *      }
     * )
     * @Method("GET")
     * @ParamConverter("announcement", class="CivixCoreBundle:Announcement")
     * @ApiDoc(
     *      resource=true,
     *      description="Returns announcement info by ID",
     *      statusCodes={
     *          200="Returns announcement's info",
     *          400="Bad Request",
     *          403="Access Denied",
     *          405="Method Not Allowed"
     *      }
     * )
     */
    public function getAction($ownerId, Announcement $announcement)
    {
        $owner = $this->getOwner($ownerId);

        if ($announcement->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('ANNOUNCEMENT_READ', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }
        
        return $this->createJSONResponse($this->jmsSerialization($announcement, ['api']));
    }

    /**
     * Create announcement.
     * 
     * @Route(
     *      "/{ownerId}/announcement",
     *      name="api_announcement_create",
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

        // if (!$this->isGranred('ANNOUNCEMENT_CREATE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $announcement = $this->jmsDeserialization(
            $request->getContent(), $this->getEntity(), ['api']
        );

        $announcement
            ->setUser($owner)
            ->setCreatedAt(new \DateTime())
        ;

        $errors = $this->getValidator()->validate($announcement, ['api']);
        if (count($errors) > 0) {
            return $this->createJSONResponse(json_encode([
                'errors' => $this->transformErrors($errors)
            ]), 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($announcement);
        $manager->flush($announcement);
        
        return $this->createJSONResponse($this->jmsSerialization($announcement, ['api']), 201);
    }

    /**
     * Update announcement.
     *
     * @Route(
     *     "/{ownerId}/announcement/{id}",
     *     name="api_announcement_update",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     },
     * )
     * @Method("PUT")
     * @ParamConverter("announcement", class="CivixCoreBundle:Announcement")
     * @ApiDoc(
     *      statusCodes={
     *          204="Announcement updated",
     *          400="Bad Request",
     *          403="Access Denied"
     *      }
     * )
     */
    public function updateAction(Request $request, $ownerId, Announcement $announcement)
    {
        $owner = $this->getOwner($ownerId);

        if ($announcement->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('ANNOUNCEMENT_UPDATE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($announcement->getPublishedAt()) {
            throw new BadRequestHttpException("You can't update published announcements");   
        }

        $announcementUpdated = $this->jmsDeserialization(
            $request->getContent(), $this->getEntity(), ['api']
        );

        // @todo NewsManager implementation
        $this->get('civix_core.announcement_manager')->update($announcement, $announcementUpdated);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($announcement);
        $manager->flush();

        // return $this->createJSONResponse('[]', 204);
        return $this->createJSONResponse(
            $this->jmsSerialization($announcement, array('api')),
            200
        );
    }

    /**
     * Delete announcement.
     * 
     * @Route(
     *     "/{ownerId}/announcement/{id}",
     *     name="api_announcement_delete",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("DELETE")
     * @ParamConverter(
     *      "announcement",
     *      class="CivixCoreBundle:Announcement"
     * )
     * @ApiDoc(
     *      statusCodes={
     *          204="Success",
     *          405="Method Not Allowed"
     *      }
     * )
     */
    public function deleteAction(Request $request, $ownerId, Announcement $announcement)
    {
        $owner = $this->getOwner($ownerId);

        if ($announcement->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('DELETE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($announcement->getPublishedAt()) {
            throw new BadRequestHttpException("You can't delete published announcements");   
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($announcement);
        $manager->flush();

        return $this->createJSONResponse('[]', 204);
    }

    /**
     * Publish announcement.
     * 
     * @Route(
     *     "/{ownerId}/announcement/{id}/publish",
     *     name="api_announcement_publish",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("PATCH")
     * @ParamConverter(
     *      "announcement",
     *      class="CivixCoreBundle:Announcement"
     * )
     * @ApiDoc(
     *      statusCodes={
     *          204="Success",
     *          405="Method Not Allowed"
     *      }
     * )
     */
    public function publishAction($ownerId, Announcement $announcement)
    {
        $owner = $this->getOwner($ownerId);

        if ($announcement->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('ANNOUNCEMENT_PUBLISH', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($announcement->getPublishedAt()) {
            throw new BadRequestHttpException("This announcement already published");
        }
        
        $packLimitState = $this->get('civix_core.package_handler')
            ->getPackageStateForAnnouncement($owner);
        
        if ($packLimitState->isAllowedWith()) {

            $announcement->setPublishedAt(new \DateTime());
            $this->getDoctrine()->getManager()->flush();

            $this->get('civix_core.push_task')->addToQueue(
                $this->getSendPushMethodName(),
                [$owner->getId(), $announcement->getContent()]
            );

            $response = $this->createJSONResponse('[]', 204);
        } else {
            $response = $this->createJSONResponse('[]', 405);
        }

        return $response;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(Announcement::class);
    }

    /**
     * Analog of getAnnouncementClass
     *
     * @return string
     */
    abstract protected function getEntity();

    /**
     * @return string
     */
    abstract protected function getSendPushMethodName();

}
