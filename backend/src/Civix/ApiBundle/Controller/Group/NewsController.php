<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Controller\AbstractNewsController;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question\GroupNews;

class NewsController extends AbstractNewsController
{
    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return GroupNews::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnerEntity()
    {
        return Group::class;
    }
}