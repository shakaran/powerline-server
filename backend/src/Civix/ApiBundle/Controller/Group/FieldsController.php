<?php

namespace Civix\ApiBundle\Controller\Group;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Civix\ApiBundle\Controller\AbstractOwnerController;
use Civix\FrontBundle\Form\Type\Group\GroupFields;

class FieldsController extends AbstractOwnerController
{
    /**
     * @todo Think about way how to provide package information
     *
     * @Route(
     *      "/{ownerId}/fields",
     *      name="civix_front_group_fields_update",
     *      requirements={
     *          "ownerId" = "\d+"
     *      }
     * )
     * @Method("POST")
     */
    public function updateFields($ownerId)
    {
        $owner = $this->getOwner($ownerId);
        $fieldsForRemove = $owner->getFields()->toArray();
        $entityManager = $this->getDoctrine()->getManager();

        // @todo Move form logic to some civix_core.fields_manager
        $requiredFieldsForm = $this->createForm(new GroupFields(), $owner);
        $requiredFieldsForm->bind($this->getRequest());

        if ($requiredFieldsForm->isValid()) {
            // filter $optionForRemove to contain Option no longer present
            foreach ($owner->getFields() as $field) {
                foreach ($fieldsForRemove as $key => $forRemove) {
                    if ($forRemove->getId() === $field->getId()) {
                        unset($fieldsForRemove[$key]);
                    }
                }
            }

            foreach ($fieldsForRemove as $field) {
                $entityManager->remove($field);
            }
            foreach ($owner->getFields() as $field) {
                $field->setGroup($currenGroup);
                $entityManager->persist($field);
            }
            $owner->updateFillFieldsRequired();
            $entityManager->flush();

            return $this->createJSONResponse("[]", 204);
        }
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
    protected function getOwnerEntity()
    {
        return Group::class;
    }
}
