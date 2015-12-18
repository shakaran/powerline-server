<?php

namespace Civix\ApiBundle\Tests\DataFixtures\ORM\Group;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Civix\CoreBundle\Entity\Group;

class LoadApprovalGroupData extends AbstractFixture implements FixtureInterface
{
	const GROUP_USERNAME = 'approval-group';
    const GROUP_PASSWORD = 'fakepassword';
    const GROUP_MANAGER_EMAIL = 'approval-group@example.com';
	
    public function load(ObjectManager $manager)
    {
        $group = new Group();
        $group->setUsername(self::GROUP_USERNAME)
            ->setManagerEmail(self::GROUP_MANAGER_EMAIL)
            ->setPassword(self::GROUP_PASSWORD);

        $group->setMembershipControl(Group::GROUP_MEMBERSHIP_APPROVAL);
        
        $this->addReference('approval-group', $group);
        $manager->persist($group);
        $manager->flush();
    }
}
