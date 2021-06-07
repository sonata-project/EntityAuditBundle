<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\Common\Collections\Collection;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\ConvertToPHPEntity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestOwnedElement;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestSecondaryOwner;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\EscapedColumnsEntity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue111Entity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue156Client;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue156Contact;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue156ContactTelephoneNumber;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue196Entity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue198Car;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue198Owner;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue308User;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue318User;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue31Reve;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue31User;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87AbstractProject;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87Organization;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87Project;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87ProjectComment;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue9Address;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue9Customer;
use SimpleThings\EntityAudit\Tests\Types\ConvertToPHPType;
use SimpleThings\EntityAudit\Tests\Types\Issue196Type;

final class IssueTest extends BaseTest
{
    protected $schemaEntities = [
        EscapedColumnsEntity::class,
        Issue87Project::class,
        Issue87ProjectComment::class,
        Issue87AbstractProject::class,
        Issue87Organization::class,
        Issue9Address::class,
        Issue9Customer::class,
        Issue87Organization::class,
        DuplicateRevisionFailureTestPrimaryOwner::class,
        DuplicateRevisionFailureTestSecondaryOwner::class,
        DuplicateRevisionFailureTestOwnedElement::class,
        Issue111Entity::class,
        Issue31User::class,
        Issue31Reve::class,
        Issue156Contact::class,
        Issue156ContactTelephoneNumber::class,
        Issue156Client::class,
        Issue198Car::class,
        Issue198Owner::class,
        Issue196Entity::class,
        Issue308User::class,
        Issue318User::class,
        ConvertToPHPEntity::class,
    ];

    protected $auditedEntities = [
        EscapedColumnsEntity::class,
        Issue87Project::class,
        Issue87ProjectComment::class,
        Issue87AbstractProject::class,
        Issue87Organization::class,
        Issue9Address::class,
        Issue9Customer::class,
        Issue87Organization::class,
        DuplicateRevisionFailureTestPrimaryOwner::class,
        DuplicateRevisionFailureTestSecondaryOwner::class,
        DuplicateRevisionFailureTestOwnedElement::class,
        Issue111Entity::class,
        Issue31User::class,
        Issue31Reve::class,
        Issue156Contact::class,
        Issue156ContactTelephoneNumber::class,
        Issue156Client::class,
        Issue196Entity::class,
        Issue198Car::class,
        Issue198Owner::class,
        Issue308User::class,
        Issue318User::class,
        ConvertToPHPEntity::class,
    ];

    protected $customTypes = [
        'issue196type' => Issue196Type::class,
        'upper' => ConvertToPHPType::class,
    ];

    /**
     * @doesNotPerformAssertions
     */
    public function testIssue31(): void
    {
        $reve = new Issue31Reve();
        $reve->setTitre('reve');

        $this->em->persist($reve);
        $this->em->flush();

        $user = new Issue31User();
        $user->setTitre('user');
        $user->setReve($reve);

        $this->em->persist($user);
        $this->em->flush();

        $this->em->remove($user);
        $this->em->flush();
    }

    public function testIssue111(): void
    {
        $this->em->getEventManager()->addEventSubscriber(new SoftDeleteableListener());

        $e = new Issue111Entity();
        $e->setStatus('test status');

        $this->em->persist($e);
        $this->em->flush($e); //#1

        $this->em->remove($e);
        $this->em->flush(); //#2

        $reader = $this->auditManager->createAuditReader();

        $ae = $reader->find(Issue111Entity::class, 1, 2);

        $this->assertInstanceOf('DateTime', $ae->getDeletedAt());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testEscapedColumns(): void
    {
        $e = new EscapedColumnsEntity();
        $e->setLeft(1);
        $e->setLft(2);
        $this->em->persist($e);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader();

        $reader->find(\get_class($e), $e->getId(), 1);
    }

    public function testIssue87(): void
    {
        $org = new Issue87Organization();
        $project = new Issue87Project();
        $project->setOrganisation($org);
        $project->setSomeProperty('some property');
        $project->setTitle('test project');
        $comment = new Issue87ProjectComment();
        $comment->setProject($project);
        $comment->setText('text comment');

        $this->em->persist($org);
        $this->em->persist($project);
        $this->em->persist($comment);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader();

        $auditedProject = $auditReader->find(\get_class($project), $project->getId(), 1);

        $this->assertSame($org->getId(), $auditedProject->getOrganisation()->getId());
        $this->assertSame('test project', $auditedProject->getTitle());
        $this->assertSame('some property', $auditedProject->getSomeProperty());

        $auditedComment = $auditReader->find(\get_class($comment), $comment->getId(), 1);
        $this->assertSame('test project', $auditedComment->getProject()->getTitle());

        $project->setTitle('changed project title');
        $this->em->flush();

        $auditedComment = $auditReader->find(\get_class($comment), $comment->getId(), 2);
        $this->assertSame('changed project title', $auditedComment->getProject()->getTitle());
    }

    public function testIssue9(): void
    {
        $address = new Issue9Address();
        $address->setAddressText('NY, Red Street 6');

        $customer = new Issue9Customer();
        $customer->setAddresses([$address]);
        $customer->setPrimaryAddress($address);

        $address->setCustomer($customer);

        $this->em->persist($customer);
        $this->em->persist($address);

        $this->em->flush(); //#1

        $reader = $this->auditManager->createAuditReader();

        $aAddress = $reader->find(\get_class($address), $address->getId(), 1);
        $this->assertSame($customer->getId(), $aAddress->getCustomer()->getId());

        /** @var Issue9Customer $aCustomer */
        $aCustomer = $reader->find(\get_class($customer), $customer->getId(), 1);

        $this->assertNotNull($aCustomer->getPrimaryAddress());
        $this->assertSame('NY, Red Street 6', $aCustomer->getPrimaryAddress()->getAddressText());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDuplicateRevisionKeyConstraintFailure(): void
    {
        $primaryOwner = new DuplicateRevisionFailureTestPrimaryOwner();
        $this->em->persist($primaryOwner);

        $secondaryOwner = new DuplicateRevisionFailureTestSecondaryOwner();
        $this->em->persist($secondaryOwner);

        $primaryOwner->addSecondaryOwner($secondaryOwner);

        $element = new DuplicateRevisionFailureTestOwnedElement();
        $this->em->persist($element);

        $primaryOwner->addElement($element);
        $secondaryOwner->addElement($element);

        $this->em->flush();

        $this->em->getUnitOfWork()->clear();

        $primaryOwner = $this->em->find(DuplicateRevisionFailureTestPrimaryOwner::class, 1);

        $this->em->remove($primaryOwner);
        $this->em->flush();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testIssue156(): void
    {
        $client = new Issue156Client();

        $number = new Issue156ContactTelephoneNumber();
        $number->setNumber('0123567890');
        $client->addTelephoneNumber($number);

        $this->em->persist($client);
        $this->em->persist($number);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader();
        $object = $auditReader->find(\get_class($number), $number->getId(), 1);
    }

    public function testIssue196(): void
    {
        $entity = new Issue196Entity();
        $entity->setSqlConversionField('THIS SHOULD BE LOWER CASE');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $persistedEntity = $this->em->find(\get_class($entity), $entity->getId());

        $auditReader = $this->auditManager->createAuditReader();
        $currentRevision = $auditReader->getCurrentRevision(\get_class($entity), $entity->getId());
        $currentRevisionEntity = $auditReader->find(\get_class($entity), $entity->getId(), $currentRevision);

        $this->assertSame(
            $persistedEntity->getSqlConversionField(),
            $currentRevisionEntity->getSqlConversionField(),
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }

    public function testIssue198(): void
    {
        $owner = new Issue198Owner();
        $car = new Issue198Car();

        $this->em->persist($owner);
        $this->em->persist($car);
        $this->em->flush();

        $owner->addCar($car);

        $this->em->persist($owner);
        $this->em->persist($car);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader();

        $car1 = $auditReader->find(\get_class($car), $car->getId(), 1);
        $this->assertNull($car1->getOwner());

        $car2 = $auditReader->find(\get_class($car), $car->getId(), 2);
        $this->assertSame($car2->getOwner()->getId(), $owner->getId());
    }

    public function testConvertToPHP(): void
    {
        $entity = new ConvertToPHPEntity();
        $entity->setSqlConversionField('TEST CONVERT TO PHP');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $persistedEntity = $this->em->find(\get_class($entity), $entity->getId());

        $auditReader = $this->auditManager->createAuditReader();
        $currentRevision = $auditReader->getCurrentRevision(\get_class($entity), $entity->getId());
        $currentRevisionEntity = $auditReader->find(\get_class($entity), $entity->getId(), $currentRevision);

        $this->assertSame(
            $persistedEntity->getSqlConversionField(),
            $currentRevisionEntity->getSqlConversionField(),
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }

    public function testIssue318(): void
    {
        $user = new Issue318User();
        $user->setAlias('alias');
        $this->em->persist($user);
        $this->em->flush();
        $userMetadata = $this->em->getClassMetadata(\get_class($user));
        $classes = [$userMetadata];
        $schema = $this->getSchemaTool()->getSchemaFromMetadata($classes);
        $schemaName = $schema->getName();
        $config = $this->getAuditManager()->getConfiguration();
        $userNotNullColumnName = 'alias';
        $userIdColumnName = 'id';
        $revisionsTableUser = $schema->getTable(sprintf(
            '%s.%sissue318user%s',
            $schemaName,
            $config->getTablePrefix(),
            $config->getTableSuffix()
        ));

        $this->assertFalse($revisionsTableUser->getColumn($userNotNullColumnName)->getNotnull());
        $this->assertFalse($revisionsTableUser->getColumn($userIdColumnName)->getAutoincrement());
    }

    public function testIssue308(): void
    {
        $user = new Issue308User();
        $child1 = new Issue308User();
        $user->addChild($child1);
        $this->em->persist($child1);
        $this->em->persist($user);
        $this->em->flush();

        $this->assertInstanceOf(Collection::class, $user->getChildren());

        $auditReader = $this->auditManager->createAuditReader();
        $auditReader->setLoadAuditedCollections(true);
        $userClass = \get_class($user);
        $revisions = $auditReader->findRevisions($userClass, $user->getId());
        $this->assertCount(1, $revisions);
        $revision = reset($revisions);
        $auditedUser = $auditReader->find($userClass, ['id' => $user->getId()], $revision->getRev());

        $this->assertInstanceOf(Collection::class, $auditedUser->getChildren());
    }
}
