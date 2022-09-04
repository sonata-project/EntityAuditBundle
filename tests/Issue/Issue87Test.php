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

namespace Sonata\EntityAuditBundle\Tests\Issue;

use Sonata\EntityAuditBundle\Tests\BaseTest;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue87AbstractProject;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue87Organization;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue87Project;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue87ProjectComment;

final class Issue87Test extends BaseTest
{
    protected $schemaEntities = [
        Issue87Project::class,
        Issue87ProjectComment::class,
        Issue87AbstractProject::class,
        Issue87Organization::class,
    ];

    protected $auditedEntities = [
        Issue87Project::class,
        Issue87ProjectComment::class,
        Issue87AbstractProject::class,
        Issue87Organization::class,
    ];

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

        $auditReader = $this->auditManager->createAuditReader($this->em);

        $projectId = $project->getId();
        static::assertNotNull($projectId);

        $auditedProject = $auditReader->find(Issue87Project::class, $projectId, 1);
        static::assertNotNull($auditedProject);

        $projectOrganisation = $auditedProject->getOrganisation();
        static::assertNotNull($projectOrganisation);
        static::assertSame($org->getId(), $projectOrganisation->getId());
        static::assertSame('test project', $auditedProject->getTitle());
        static::assertSame('some property', $auditedProject->getSomeProperty());

        $commentId = $comment->getId();
        static::assertNotNull($commentId);

        $auditedComment = $auditReader->find(Issue87ProjectComment::class, $commentId, 1);
        static::assertNotNull($auditedComment);
        $commentProject = $auditedComment->getProject();
        static::assertNotNull($commentProject);
        static::assertSame('test project', $commentProject->getTitle());

        $project->setTitle('changed project title');
        $this->em->flush();

        $auditedComment = $auditReader->find(Issue87ProjectComment::class, $commentId, 2);
        static::assertNotNull($auditedComment);
        $commentProject = $auditedComment->getProject();
        static::assertNotNull($commentProject);
        static::assertSame('changed project title', $commentProject->getTitle());
    }
}
