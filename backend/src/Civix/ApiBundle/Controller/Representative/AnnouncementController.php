<?php

namespace Civix\ApiBundle\Controller\Representative;

use Civix\ApiBundle\Controller\AbstractAnnouncementController;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Announcement\RepresentativeAnnouncement;

class AnnouncementController extends AbstractAnnouncementController
{
    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return RepresentativeAnnouncement::class;
    }

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
    protected function getSendPushMethodName()
    {
        return 'sendRepresentativeAnnouncementPush';
    }
}