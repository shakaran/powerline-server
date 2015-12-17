<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Controller\AbstractLeaderEventController;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question\GroupEvent;

class LeaderEventController extends AbstractLeaderEventController
{
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
        return GroupEvent::class;
    }
}