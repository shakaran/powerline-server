<?php

namespace Civix\ApiBundle\Controller\Representative;

use Civix\ApiBundle\Controller\AbstractLeaderEventController;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Poll\Question\RepresentativeEvent;

class LeaderEventController extends AbstractLeaderEventController
{
    /**
     * {@inheritdoc}
     */
    protected function getOwnerEntity()
    {
        return Representative::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return RepresentativeEvent::class;
    }
}