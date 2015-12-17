<?php

namespace Civix\ApiBundle\Controller\Group;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Civix\ApiBundle\Controller\AbstractOwnerController;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MemberController extends AbstractOwnerController
{
    /**
     * @todo Think about way how to provide package information
     *
     * @Route(
     *     "/{ownerId}/members",
     *     name="api_group_members",
     *     requirements={
     *         "ownerId" = "\d+"
     *     }
     * )
     * @Method("GET")
     */
    public function membersAction(Request $request, $ownerId)
    {
        $owner = $this->getOwner($ownerId);
        $status = ($owner->getMembershipControl() == Group::GROUP_MEMBERSHIP_APPROVAL) ?
            $owner->getMembershipControl() : null;

        return $this->createPaginatedJSONResponseFromQuery(
            $this->getRepository()->getUsersByGroupQuery($owner, $status),
            ['api'],
            200,
            $request->get('page', 1),
            20
        );
    }

    /**
     * @Route(
     *     "/{ownerId}/members/{id}",
     *     name="api_group_members_delete",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("UNLINK")
     * @ParamConverter("user", class="CivixCoreBundle:User")
     */
    public function memberRemoveAction($ownerId, User $user)
    {
        $owner = $this->getOwner($ownerId);
        $this->get('civix_core.group_manager')
            ->unjoinGroup($user, $owner);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->createJSONResponse("[]", 204);
    }

    /**
     * @todo Think about way how to provide package information
     *
     * List members awaiting for membership approval.
     *
     * @Route(
     *     "/{ownerId}/members/approvals",
     *     name="api_group_members_approvals",
     *     requirements={
     *         "ownerId" = "\d+"
     *     }
     * )
     * @Method("GET")
     */
    public function manageApprovalsAction(Request $request, $ownerId)
    {
        $owner = $this->getOwner($ownerId);
        return $this->createPaginatedJSONResponseFromQuery(
            $this->getRepository()->getUsersByGroupQuery($owner, UserGroup::STATUS_PENDING),
            ['api'],
            200,
            $request->get('page', 1),
            20
        );
    }

    /**
     * Approve membership.
     *
     * @Route(
     *     "/{ownerId}/members/{id}/approve",
     *     name="api_group_members_approve",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("PATCH")
     * @ParamConverter("user", class="CivixCoreBundle:User")
     */
    public function approveUser($ownerId, User $user)
    {
        $owner = $this->getOwner($ownerId);
        $entityManager = $this->getDoctrine()->getManager();

        $userGroup = $this->getRepository()->isJoinedUser($owner, $user);
        if (!$userGroup) {
            throw new BadRequestHttpException();
        }

        $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        $entityManager->persist($userGroup);
        $entityManager->flush();

        $this->get('civix_core.social_activity_manager')->noticeGroupJoiningApproved($userGroup);

        return $this->createJSONResponse('[]', 204);
    }

    /**
     * @todo Think about way how to provide package information
     *
     * @Route(
     *     "/{ownerId}/members/{id}/fields",
     *     name="api_group_members_fields",
     *     requirements={
     *         "ownerId" = "\d+",
     *         "id" = "\d+"
     *     }
     * )
     * @Method("GET")
     * @ParamConverter("user", class="CivixCoreBundle:User")
     */
    public function getUserFieldsAction($ownerId, User $user)
    {
        $owner = $this->getOwner($ownerId);

        $entityManager = $this->getDoctrine()->getManager();
        $fieldValues = $entityManager->getRepository('CivixCoreBundle:Group\FieldValue')
            ->getFieldsValuesByUser($user, $owner);

        return $this->createJSONResponse(
            $this->jmsSerialization($fieldValues, ['api']),
            200
        );
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(UserGroup::class);
    }

    /**
     * @return string
     */
    protected function getOwnerEntity()
    {
        return Group::class;
    }
}
