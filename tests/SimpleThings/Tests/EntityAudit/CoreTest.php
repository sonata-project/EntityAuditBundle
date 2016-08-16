<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\Tests\EntityAudit;

use SimpleThings\Tests\EntityAudit\Fixtures\Core\ArticleAudit;
use SimpleThings\Tests\EntityAudit\Fixtures\Core\Cat;
use SimpleThings\Tests\EntityAudit\Fixtures\Core\Dog;
use SimpleThings\Tests\EntityAudit\Fixtures\Core\Fox;
use SimpleThings\Tests\EntityAudit\Fixtures\Core\Rabbit;
use SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit;

class CoreTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\ArticleAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\AnimalAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Fox',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Rabbit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\PetAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Cat',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Dog'
    );

    protected $auditedEntities = array(
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\ArticleAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\AnimalAudit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Rabbit',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Fox',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Cat',
        'SimpleThings\Tests\EntityAudit\Fixtures\Core\Dog'
    );

    public function testAuditable()
    {
        $user = new UserAudit("beberlei");
        $article = new ArticleAudit("test", "yadda!", $user, 'text');
        $rabbit = new Rabbit('rabbit', 'white');
        $foxy = new Fox('foxy', 60);
        $doggy = new Dog('woof', 80);
        $cat = new Cat('pusheen', '#b5a89f');

        $this->_em->persist($user);
        $this->_em->persist($article);
        $this->_em->persist($rabbit);
        $this->_em->persist($foxy);
        $this->_em->persist($doggy);
        $this->_em->persist($cat);
        $this->_em->flush();

        $this->assertEquals(1, count($this->_em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(1, count($this->_em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit')));
        $this->assertEquals(1, count($this->_em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
        $this->assertEquals(2, count($this->_em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit')));

        $article->setText("oeruoa");
        $rabbit->setName('Rabbit');
        $rabbit->setColor('gray');
        $foxy->setName('Foxy');
        $foxy->setTailLength(55);

        $this->_em->flush();

        $this->assertEquals(2, count($this->_em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->_em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
        $this->assertEquals(4, count($this->_em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit')));

        $this->_em->remove($user);
        $this->_em->remove($article);
        $this->_em->remove($rabbit);
        $this->_em->remove($foxy);
        $this->_em->flush();

        $this->assertEquals(3, count($this->_em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->_em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit')));
        $this->assertEquals(3, count($this->_em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
        $this->assertEquals(6, count($this->_em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit')));
    }

    public function testFind()
    {
        $user = new UserAudit("beberlei");
        $foxy = new Fox('foxy', 55);
        $cat = new Cat('pusheen', '#b5a89f');

        $this->_em->persist($cat);
        $this->_em->persist($user);
        $this->_em->persist($foxy);
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);
        $auditUser = $reader->find(get_class($user), $user->getId(), 1);

        $this->assertInstanceOf(get_class($user), $auditUser, "Audited User is also a User instance.");
        $this->assertEquals($user->getId(), $auditUser->getId(), "Ids of audited user and real user should be the same.");
        $this->assertEquals($user->getName(), $auditUser->getName(), "Name of audited user and real user should be the same.");
        $this->assertFalse($this->_em->contains($auditUser), "Audited User should not be in the identity map.");
        $this->assertNotSame($user, $auditUser, "User and Audited User instances are not the same.");

        $auditFox = $reader->find(get_class($foxy), $foxy->getId(), 1);

        $this->assertInstanceOf(get_class($foxy), $auditFox, "Audited SINGLE_TABLE class keeps it's class.");
        $this->assertEquals($foxy->getId(), $auditFox->getId(), "Ids of audited SINGLE_TABLE class and real SINGLE_TABLE class should be the same.");
        $this->assertEquals($foxy->getName(), $auditFox->getName(), "Loaded and original attributes should be the same for SINGLE_TABLE inheritance.");
        $this->assertEquals($foxy->getTailLength(), $auditFox->getTailLength(), "Loaded and original attributes should be the same for SINGLE_TABLE inheritance.");
        $this->assertFalse($this->_em->contains($auditFox), "Audited SINGLE_TABLE inheritance class should not be in the identity map.");
        $this->assertNotSame($this, $auditFox, "Audited and new entities should not be the same object for SINGLE_TABLE inheritance.");

        $auditCat = $reader->find(get_class($cat), $cat->getId(), 1);

        $this->assertInstanceOf(get_class($cat), $auditCat, "Audited JOINED class keeps it's class.");
        $this->assertEquals($cat->getId(), $auditCat->getId(), "Ids of audited JOINED class and real JOINED class should be the same.");
        $this->assertEquals($cat->getName(), $auditCat->getName(), "Loaded and original attributes should be the same for JOINED inheritance.");
        $this->assertEquals($cat->getColor(), $auditCat->getColor(), "Loaded and original attributes should be the same for JOINED inheritance.");
        $this->assertFalse($this->_em->contains($auditCat), "Audited JOINED inheritance class should not be in the identity map.");
        $this->assertNotSame($this, $auditCat, "Audited and new entities should not be the same object for JOINED inheritance.");
    }

    public function testFindNoRevisionFound()
    {
        $reader = $this->_auditManager->createAuditReader($this->_em);

        $this->setExpectedException(
            'SimpleThings\EntityAudit\Exception\NoRevisionFoundException',
            "No revision of class 'SimpleThings\\Tests\\EntityAudit\\Fixtures\\Core\\UserAudit' (1) was found at revision 1 or before. The entity did not exist at the specified revision yet."
        );
        $auditUser = $reader->find('SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit', 1, 1);
    }

    public function testFindNotAudited()
    {
        $reader = $this->_auditManager->createAuditReader($this->_em);

        $this->setExpectedException(
            'SimpleThings\EntityAudit\Exception\NotAuditedException',
            "Class 'stdClass' is not audited."
        );
        $auditUser = $reader->find("stdClass", 1, 1);
    }

    public function testFindRevisionHistory()
    {
        $user = new UserAudit("beberlei");

        $this->_em->persist($user);
        $this->_em->flush();

        $article = new ArticleAudit("test", "yadda!", $user, 'text');

        $this->_em->persist($article);
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);
        $revisions = $reader->findRevisionHistory();

        $this->assertEquals(2, count($revisions));
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[0]->getUsername());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[1]->getUsername());
    }

    public function testFindEntitesChangedAtRevision()
    {
        $user = new UserAudit("beberlei");
        $article = new ArticleAudit("test", "yadda!", $user, 'text');
        $foxy = new Fox('foxy', 50);
        $rabbit = new Rabbit('rabbit', 'white');
        $cat = new Cat('pusheen', '#b5a89f');
        $dog = new Dog('doggy', 80);

        $this->_em->persist($dog);
        $this->_em->persist($cat);
        $this->_em->persist($foxy);
        $this->_em->persist($rabbit);
        $this->_em->persist($user);
        $this->_em->persist($article);
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(1);

        //duplicated entries means a bug with discriminators
        $this->assertEquals(6, count($changedEntities));
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);

        $this->assertEquals('SimpleThings\Tests\EntityAudit\Fixtures\Core\ArticleAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('INS', $changedEntities[0]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
        $this->assertInstanceOf('SimpleThings\Tests\EntityAudit\Fixtures\Core\ArticleAudit', $changedEntities[0]->getEntity());

        $this->assertEquals('SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit', $changedEntities[1]->getClassName());
        $this->assertEquals('INS', $changedEntities[1]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[1]->getId());
        $this->assertInstanceOf('SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit', $changedEntities[1]->getEntity());
    }

    public function testFindRevisions()
    {
        $user = new UserAudit("beberlei");
        $foxy = new Fox('foxy', 30);
        $rabbit = new Rabbit('rabbit', 'white');
        $cat = new Cat('pusheen', '#b5a89f');
        $dog = new Dog('doggy', 80);

        $this->_em->persist($dog);
        $this->_em->persist($cat);
        $this->_em->persist($user);
        $this->_em->persist($foxy);
        $this->_em->persist($rabbit);
        $this->_em->flush();

        $foxy->setName('Foxy');
        $dog->setName('doge');
        $user->setName("beberlei2");
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);
        $revisions = $reader->findRevisions(get_class($user), $user->getId());

        $this->assertEquals(2, count($revisions));
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[0]->getUsername());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[1]->getUsername());

        //SINGLE_TABLE should have separate revision history
        $this->assertEquals(2, count($reader->findRevisions(get_class($foxy), $foxy->getId())));
        $this->assertEquals(1, count($reader->findRevisions(get_class($rabbit), $rabbit->getId())));
        //JOINED too
        $this->assertEquals(2, count($reader->findRevisions(get_class($dog), $dog->getId())));
        $this->assertEquals(1, count($reader->findRevisions(get_class($cat), $cat->getId())));
    }

    public function testFindCurrentRevision()
    {
        $user = new UserAudit('Broncha');

        $this->_em->persist($user);
        $this->_em->flush();

        $user->setName("Rajesh");
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $revision = $reader->getCurrentRevision(get_class($user), $user->getId());
        $this->assertEquals(2, $revision);

        $user->setName("David");
        $this->_em->flush();

        $revision = $reader->getCurrentRevision(get_class($user), $user->getId());
        $this->assertEquals(3, $revision);
    }

    public function testGlobalIgnoreColumns()
    {
        $user = new UserAudit("welante");
        $article = new ArticleAudit("testcolumn", "yadda!", $user, 'text');

        $this->_em->persist($user);
        $this->_em->persist($article);
        $this->_em->flush();

        $article->setText("testcolumn2");
        $this->_em->persist($article);
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $revision = $reader->getCurrentRevision(get_class($article), $article->getId());
        $this->assertEquals(2, $revision);

        $article->setIgnoreme("textnew");
        $this->_em->persist($article);
        $this->_em->flush();

        $revision = $reader->getCurrentRevision(get_class($article), $article->getId());
        $this->assertEquals(2, $revision);
    }

    public function testDeleteUnInitProxy()
    {
        $user = new UserAudit("beberlei");

        $this->_em->persist($user);
        $this->_em->flush();

        unset($user);
        $this->_em->clear();

        $user = $this->_em->getReference("SimpleThings\\Tests\\EntityAudit\\Fixtures\\Core\\UserAudit", 1);
        $this->_em->remove($user);
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(2);

        $this->assertEquals(1, count($changedEntities));
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);
        $this->assertEquals('SimpleThings\Tests\EntityAudit\Fixtures\Core\UserAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('DEL', $changedEntities[0]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
    }
}
