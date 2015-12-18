<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Announcement;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractOwnerController extends BaseController
{
    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getOwnerRepository()
    {
        return $this->getDoctrine()->getRepository(
            $this->getOwnerEntity()
        );
    }

    /**
     * @param integer $ownerId
     *
     * @return Group|Representative
     * @throws \Exception
     */
    protected function getOwner($ownerId)
    {
        $owner = $this->getOwnerRepository()->find($ownerId);

        if (is_subclass_of($owner, $this->getOwnerEntity())) {
            return $owner;
        }

        throw new BadRequestHttpException();
    }

    /**
     * @return string
     */
    abstract protected function getOwnerEntity();
}