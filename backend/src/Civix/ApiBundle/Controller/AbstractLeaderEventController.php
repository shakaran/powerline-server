<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question\LeaderEvent;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractLeaderEventController extends AbstractOwnerController
{
    /**
     * @todo Add educational context implementation (probably as separate api calls)
     *
     * Create leader event.
     *
     * @Route(
     *      "/{ownerId}/leader-event",
     *      name="api_leader_event_create",
     *      requirements={
     *          "ownerId" = "\d+"
     *      }
     * )
     * @Method("POST")
     * @ApiDoc(
     *      statusCodes={
     *          201="Created",
     *          400="Bad Request",
     *          403="Access Denied"
     *      }
     * )
     */
    public function createAction(Request $request, $ownerId)
    {
        $manager = $this->getDoctrine()->getManager();
        $owner = $this->getOwner($ownerId);

        if ($leaderEvent->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('LEADER_EVENT_DELETE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        // @todo Ensure it work same way as form
        // @todo Validation?
        $leaderEvent = $this->jmsDeserialization(
            $request->getContent(), $this->getEntity(), ['api']
        );

        // Predefined options
        $optionValues = ['Yes, I will attend', 'No, I will not attend'];
        foreach ($optionValues as $optionTitle) {
            $option = new Option();
            $option->setValue($optionTitle);
            $leaderEvent->addOption($option);

            $manager->persist($option);
        }

        $leaderEvent->setUser($owner);

        $manager->persist($leaderEvent);
        $manager->flush();

        return $this->createJSONResponse("[]", 201);
    }

    /**
     * @todo Add educational context implementation (probably as separate api calls)
     *
     * Update leader event.
     *
     * @Route(
     *     "/{ownerId}/leader-event/{id}",
     *     name="api_leader_event_update",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     },
     * )
     * @Method("PUT")
     * @ParamConverter("leaderEvent", class="CivixCoreBundle:Poll\Question\LeaderEvent")
     * @ApiDoc(
     *      statusCodes={
     *          204="Updated",
     *          400="Bad Request",
     *          403="Access Denied"
     *      }
     * )
     */
    public function editAction(Request $request, $ownerId, LeaderEvent $leaderEvent)
    {
        $owner = $this->getOwner($ownerId);

        if ($leaderEvent->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('LEADER_EVENT_DELETE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($leaderEvent->getPublishedAt()) {
            throw new BadRequestHttpException("You can't edit published leader events");
        }

        $updatedLeaderEvent = $this->jmsDeserialization(
            $request->getContent(), $this->getEntity(), ['api']
        );

        // @todo Convert form logic to civix_core.leader_event_manager service
        $this->get('civix_core.leader_event_manager')->update($leaderEvent, $updatedLeaderEvent);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($leaderEvent);
        $manager->flush();

        return $this->createJSONResponse("[]", 204);
    }

    /**
     * Delete leader event.
     *
     * @Route(
     *     "/{ownerId}/leader-event/{id}",
     *     name="api_leader_event_delete",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("DELETE")
     * @ParamConverter("leaderEvent", class="CivixCoreBundle:Poll\Question\LeaderEvent")
     * @ApiDoc(
     *      statusCodes={
     *          204="Success",
     *          405="Method Not Allowed"
     *      }
     * )
     */
    public function deleteAction(Request $request, $ownerId, LeaderEvent $leaderEvent)
    {
        $owner = $this->getOwner($ownerId);

        if ($leaderEvent->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('LEADER_EVENT_DELETE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($leaderEvent->getPublishedAt()) {
            throw new BadRequestHttpException("You can't delete published leader events");
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($leaderEvent);
        $manager->flush();

        return $this->createJSONResponse("[]", 204);
    }

    /**
     * Publish leader event.
     *
     * @Route(
     *     "/{ownerId}/leader-event/{id}/publish",
     *     name="api_leader_event_publish",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("PATCH")
     * @ParamConverter("leaderEvent", class="CivixCoreBundle:Poll\Question\LeaderEvent")
     * @ApiDoc(
     *      statusCodes={
     *          204="Success",
     *          405="Method Not Allowed",
     *          500="Error"
     *      }
     * )
     */
    public function publishAction(Request $request, $ownerId, LeaderEvent $leaderEvent)
    {
        $owner = $this->getOwner($ownerId);

        if ($leaderEvent->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('LEADER_EVENT_PUBLISH', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        if ($leaderEvent->getPublishedAt()) {
            throw new BadRequestHttpException("This leader event already published");
        }

        $this->getDoctrine()
            ->getRepository('CivixCoreBundle:HashTag')->addForQuestion($leaderEvent);

        $ignore = new Option();
        $ignore->setValue('Ignore')->setQuestion($leaderEvent);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($ignore);
        $manager->flush($ignore);

        $result = $this->get('civix_core.activity_update')
            ->publishLeaderEventToActivity($leaderEvent);

        if ($result instanceof Activity) {
            return $this->createJSONResponse("[]", 204);
        } else {
            return $this->createJSONResponse($this->jmsSerialization($result), 500);
        }
    }

    /**
     * @return bool
     */
    protected function isAvailableGroupSection()
    {
        $packLimitState = $this->get('civix_core.package_handler')
            ->getPackageStateForGroupDivisions($this->getUser());

        return $packLimitState->isAllowed();
    }

    /**
     * @param $event
     * @return bool
     */
    protected function isShowGroupSections($event)
    {
        return ($event instanceof GroupSectionInterface) &&
        $this->isAvailableGroupSection();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        /* @var $repository \Civix\CoreBundle\Repository\Poll\LeaderEventRepository */
        return $this->getDoctrine()->getRepository(
            $this->getEntity()
        );
    }

    /**
     * @return string
     */
    protected function getEntity()
    {
        return LeaderEvent::class;
    }
}

