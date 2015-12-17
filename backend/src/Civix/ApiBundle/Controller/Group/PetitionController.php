<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\CoreBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Civix\ApiBundle\Controller\AbstractPetitionController;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Poll\Question\GroupPetition;
use Civix\CoreBundle\Entity\Customer\Card;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PetitionController extends AbstractPetitionController
{
    /**
     * @Route(
     *     "/{ownerId}/petition/{id}/invite",
     *      name="api_petition_invite",
     *      requirements={
     *          "ownerId" = "\d+",
     *          "id" = "\d+"
     *      },
     * )
     * @ParamConverter(
     *      "petition",
     *      class="CivixCoreBundle:Poll\Question\Petition",
     *      options={"repository_method" = "getPublishPetitonById"}
     * )
     */
    public function inviteAction(Request $request, Petition $petition)
    {
        $owner = $this->getOwner($ownerId);

        if ($petition->getUser() !== $owner) {
            throw new BadRequestHttpException();
        }

        // if (!$this->isGranred('PETITION_INVITE', $owner)) {
        //     throw new AccessDeniedHttpException();
        // }

        $answers = $this->getRepository()->getSignedUsersNotInGroup($petition, $owner);

        if (!empty($answers)) {
            $package = $this->get('civix_core.subscription_manager')
                ->getPackage($owner);
            $packageInviteAmount = $package->getSumForPetitionInvites();

            if (0 < $packageInviteAmount) {

                /* @var Customer $customer */
                $customer = $this->get('civix_core.customer_manager')
                    ->getCustomerByUser($this->getUser());

                /* @var Card $card */
                $card = $this->getDoctrine()->getRepository(Card::class)
                    ->findOneByCustomer($customer);

                if (!$card) {
                    // @todo throw error
                }

                $paymentHistory = $this->get('civix_core.payments')
                    ->buyPetitionsInvites($card, $customer, $packageInviteAmount * 100);

                if (!$paymentHistory->isSucceeded()) {
                    // @todo throw error
                }
            }

            $this->get('civix_core.invite_sender')
                ->sendInviteForPetition($answers, $owner);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($owner);
            $manager->flush();
        }

        return $this->createJSONResponse("[]", 204);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnerEntity()
    {
        return Group::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return GroupPetition::class;
    }
}
