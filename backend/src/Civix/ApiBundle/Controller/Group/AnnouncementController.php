<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Controller\AbstractAnnouncementController;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Announcement\GroupAnnouncement;

class AnnouncementController extends AbstractAnnouncementController
{
    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return GroupAnnouncement::class;
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
    protected function getSendPushMethodName()
    {
        return 'sendGroupAnnouncementPush';
    }
}