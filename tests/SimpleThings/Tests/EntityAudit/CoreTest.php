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

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\ORM\Mapping AS ORM;

class CoreTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\EntityAudit\Tests\ArticleAudit',
        'SimpleThings\EntityAudit\Tests\UserAudit',
        'SimpleThings\EntityAudit\Tests\AnimalAudit',
        'SimpleThings\EntityAudit\Tests\Fox',
        'SimpleThings\EntityAudit\Tests\Rabbit',
        'SimpleThings\EntityAudit\Tests\PetAudit',
        'SimpleThings\EntityAudit\Tests\Cat',
        'SimpleThings\EntityAudit\Tests\Dog'
    );

    protected $auditedEntities = array(
        'SimpleThings\EntityAudit\Tests\ArticleAudit',
        'SimpleThings\EntityAudit\Tests\UserAudit',
        'SimpleThings\EntityAudit\Tests\AnimalAudit',
        'SimpleThings\EntityAudit\Tests\Rabbit',
        'SimpleThings\EntityAudit\Tests\Fox',
        'SimpleThings\EntityAudit\Tests\Cat',
        'SimpleThings\EntityAudit\Tests\Dog'
    );

    public function testAuditable()
    {
        $user = new UserAudit("beberlei");
        $article = new ArticleAudit("test", "yadda!", $user, 'text');
        $rabbit = new Rabbit('rabbit', 'white');
        $foxy = new Fox('foxy', 60);
        $doggy = new Dog('woof', 80);
        $cat = new Cat('pusheen', '#b5a89f');

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->persist($rabbit);
        $this->em->persist($foxy);
        $this->em->persist($doggy);
        $this->em->persist($cat);
        $this->em->flush();

        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit')));
        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit')));

        $article->setText("oeruoa");
        $rabbit->setName('Rabbit');
        $rabbit->setColor('gray');
        $foxy->setName('Foxy');
        $foxy->setTailLength(55);

        $this->em->flush();

        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
        $this->assertEquals(4, count($this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit')));

        $this->em->remove($user);
        $this->em->remove($article);
        $this->em->remove($rabbit);
        $this->em->remove($foxy);
        $this->em->flush();

        $this->assertEquals(3, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit')));
        $this->assertEquals(3, count($this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
        $this->assertEquals(6, count($this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit')));
    }

    public function testFind()
    {
        $user = new UserAudit("beberlei");
        $foxy = new Fox('foxy', 55);
        $cat = new Cat('pusheen', '#b5a89f');

        $this->em->persist($cat);
        $this->em->persist($user);
        $this->em->persist($foxy);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $auditUser = $reader->find(get_class($user), $user->getId(), 1);

        $this->assertInstanceOf(get_class($user), $auditUser, "Audited User is also a User instance.");
        $this->assertEquals($user->getId(), $auditUser->getId(), "Ids of audited user and real user should be the same.");
        $this->assertEquals($user->getName(), $auditUser->getName(), "Name of audited user and real user should be the same.");
        $this->assertFalse($this->em->contains($auditUser), "Audited User should not be in the identity map.");
        $this->assertNotSame($user, $auditUser, "User and Audited User instances are not the same.");

        $auditFox = $reader->find(get_class($foxy), $foxy->getId(), 1);

        $this->assertInstanceOf(get_class($foxy), $auditFox, "Audited SINGLE_TABLE class keeps it's class.");
        $this->assertEquals($foxy->getId(), $auditFox->getId(), "Ids of audited SINGLE_TABLE class and real SINGLE_TABLE class should be the same.");
        $this->assertEquals($foxy->getName(), $auditFox->getName(), "Loaded and original attributes should be the same for SINGLE_TABLE inheritance.");
        $this->assertEquals($foxy->getTailLength(), $auditFox->getTailLength(), "Loaded and original attributes should be the same for SINGLE_TABLE inheritance.");
        $this->assertFalse($this->em->contains($auditFox), "Audited SINGLE_TABLE inheritance class should not be in the identity map.");
        $this->assertNotSame($this, $auditFox, "Audited and new entities should not be the same object for SINGLE_TABLE inheritance.");

        $auditCat = $reader->find(get_class($cat), $cat->getId(), 1);

        $this->assertInstanceOf(get_class($cat), $auditCat, "Audited JOINED class keeps it's class.");
        $this->assertEquals($cat->getId(), $auditCat->getId(), "Ids of audited JOINED class and real JOINED class should be the same.");
        $this->assertEquals($cat->getName(), $auditCat->getName(), "Loaded and original attributes should be the same for JOINED inheritance.");
        $this->assertEquals($cat->getColor(), $auditCat->getColor(), "Loaded and original attributes should be the same for JOINED inheritance.");
        $this->assertFalse($this->em->contains($auditCat), "Audited JOINED inheritance class should not be in the identity map.");
        $this->assertNotSame($this, $auditCat, "Audited and new entities should not be the same object for JOINED inheritance.");
    }

    public function testFindNoRevisionFound()
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->setExpectedException(
            "SimpleThings\EntityAudit\Exception\NoRevisionFoundException",
            "No revision of class 'SimpleThings\EntityAudit\Tests\UserAudit' (1) was found at revision 1 or before. The entity did not exist at the specified revision yet."
        );
        $auditUser = $reader->find("SimpleThings\EntityAudit\Tests\UserAudit", 1, 1);
    }

    public function testFindNotAudited()
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->setExpectedException(
            "SimpleThings\EntityAudit\Exception\NotAuditedException",
            "Class 'stdClass' is not audited."
        );
        $auditUser = $reader->find("stdClass", 1, 1);
    }

    public function testFindRevisionHistory()
    {
        $user = new UserAudit("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        $article = new ArticleAudit("test", "yadda!", $user, 'text');

        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
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

        $this->em->persist($dog);
        $this->em->persist($cat);
        $this->em->persist($foxy);
        $this->em->persist($rabbit);
        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(1);

        //duplicated entries means a bug with discriminators
        $this->assertEquals(6, count($changedEntities));
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);

        $this->assertEquals('SimpleThings\EntityAudit\Tests\ArticleAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('INS', $changedEntities[0]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
        $this->assertInstanceOf('SimpleThings\EntityAudit\Tests\ArticleAudit', $changedEntities[0]->getEntity());

        $this->assertEquals('SimpleThings\EntityAudit\Tests\UserAudit', $changedEntities[1]->getClassName());
        $this->assertEquals('INS', $changedEntities[1]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[1]->getId());
        $this->assertInstanceOf('SimpleThings\EntityAudit\Tests\UserAudit', $changedEntities[1]->getEntity());
    }

    public function testFindRevisions()
    {
        $user = new UserAudit("beberlei");
        $foxy = new Fox('foxy', 30);
        $rabbit = new Rabbit('rabbit', 'white');
        $cat = new Cat('pusheen', '#b5a89f');
        $dog = new Dog('doggy', 80);

        $this->em->persist($dog);
        $this->em->persist($cat);
        $this->em->persist($user);
        $this->em->persist($foxy);
        $this->em->persist($rabbit);
        $this->em->flush();

        $foxy->setName('Foxy');
        $dog->setName('doge');
        $user->setName("beberlei2");
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
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

        $this->em->persist($user);
        $this->em->flush();

        $user->setName("Rajesh");
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $revision = $reader->getCurrentRevision(get_class($user), $user->getId());
        $this->assertEquals(2, $revision);

        $user->setName("David");
        $this->em->flush();

        $revision = $reader->getCurrentRevision(get_class($user), $user->getId());
        $this->assertEquals(3, $revision);
    }

    public function testGlobalIgnoreColumns()
    {
        $user = new UserAudit("welante");
        $article = new ArticleAudit("testcolumn", "yadda!", $user, 'text');

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $article->setText("testcolumn2");
        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $revision = $reader->getCurrentRevision(get_class($article), $article->getId());
        $this->assertEquals(2, $revision);

        $article->setIgnoreme("textnew");
        $this->em->persist($article);
        $this->em->flush();

        $revision = $reader->getCurrentRevision(get_class($article), $article->getId());
        $this->assertEquals(2, $revision);
    }

    public function testDeleteUnInitProxy()
    {
        $user = new UserAudit("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        unset($user);
        $this->em->clear();

        $user = $this->em->getReference("SimpleThings\\EntityAudit\\Tests\\UserAudit", 1);
        $this->em->remove($user);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(2);

        $this->assertEquals(1, count($changedEntities));
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);
        $this->assertEquals('SimpleThings\EntityAudit\Tests\UserAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('DEL', $changedEntities[0]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
    }
}

/**
 * @ORM\Entity
 */
class ArticleAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="string", name="my_title_column") */
    private $title;

    /** @ORM\Column(type="text") */
    private $text;

    /** @ORM\Column(type="text") */
    private $ignoreme;

    /** @ORM\ManyToOne(targetEntity="UserAudit") */
    private $author;

    function __construct($title, $text, $author, $ignoreme)
    {
        $this->title = $title;
        $this->text = $text;
        $this->author = $author;
        $this->ignoreme = $ignoreme;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function setIgnoreme($ignoreme)
    {
        $this->ignoreme = $ignoreme;
    }
}

/**
 * @ORM\Entity
 */
class UserAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;
    /** @ORM\Column(type="string") */
    private $name;

    function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"rabbit" = "Rabbit", "fox" = "Fox"})
 */
class AnimalAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="string") */
    private $name;

    function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

/**
 * @ORM\Entity
 */
class Rabbit extends AnimalAudit
{
    /** @ORM\Column(type="string", name="cute_rabbit_color") */
    private $color;

    function __construct($name, $color)
    {
        $this->color = $color;
        parent::__construct($name);
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }
}

/**
 * @ORM\Entity
 */
class Fox extends AnimalAudit
{
    /** @ORM\Column(type="integer", name="fox_tail_length") */
    private $tailLength;

    function __construct($name, $tailLength)
    {
        $this->tailLength = $tailLength;
        parent::__construct($name);
    }

    public function getTailLength()
    {
        return $this->tailLength;
    }

    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"cat" = "Cat", "dog" = "Dog"})
 */
class PetAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="string") */
    private $name;

    function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

/**
 * @ORM\Entity
 */
class Cat extends PetAudit
{
    /** @ORM\Column(type="string", name="cute_cat_color") */
    private $color;

    function __construct($name, $color)
    {
        $this->color = $color;
        parent::__construct($name);
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }
}

/**
 * @ORM\Entity
 */
class Dog extends PetAudit
{
    /** @ORM\Column(type="integer", name="dog_tail_length") */
    private $tailLength;

    function __construct($name, $tailLength)
    {
        $this->tailLength = $tailLength;
        parent::__construct($name);
    }

    public function getTailLength()
    {
        return $this->tailLength;
    }

    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
    }
}