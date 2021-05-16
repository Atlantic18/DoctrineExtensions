<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Handler\Company;
use Sluggable\Fixture\Handler\User;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class UserRelativeSlugHandlerTest extends BaseTestCaseORM
{
    public const USER = 'Sluggable\\Fixture\\Handler\\User';
    public const COMPANY = 'Sluggable\\Fixture\\Handler\\Company';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testRelativeSlug()
    {
        $company = new Company();
        $company->setTitle('KnpLabs');
        $this->em->persist($company);

        $gedi = new User();
        $gedi->setUsername('Gedi');
        $gedi->setCompany($company);
        $this->em->persist($gedi);

        $this->em->flush();

        $this->assertEquals('knplabs/gedi', $gedi->getSlug(), 'relative slug is invalid');

        $company->setTitle('KnpLabs Nantes');
        $this->em->persist($company);
        $this->em->flush();

        $this->assertEquals('knplabs-nantes/gedi', $gedi->getSlug(), 'relative slug is invalid');
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::USER,
            self::COMPANY,
        ];
    }
}
