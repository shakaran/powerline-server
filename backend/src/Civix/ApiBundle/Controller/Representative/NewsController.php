<?php

namespace Civix\ApiBundle\Controller\Representative;

use Civix\ApiBundle\Controller\AbstractNewsController;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Poll\Question\RepresentativeNews;

class NewsController extends AbstractNewsController
{
    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return RepresentativeNews::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnerEntity()
    {
        return Representative::class;
    }
}