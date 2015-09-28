<?php

namespace Civix\CoreBundle\Entity\Notification;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AndroidEndpoint extends AbstractEndpoint
{
    public function getPlatformMessage($message, $type, $entityData)
    {
        return json_encode(array('GCM' => json_encode(array('data' => array(
            'message' => $message,
            'type' => $type,
            'entity' => json_encode($entityData),
            'title' => $type,
            'image' => 'www/images/notification_image.jpg',
        )))));
    }
}
