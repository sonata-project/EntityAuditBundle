<?php

/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
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

use Doctrine\ORM\Mapping as ORM;

class MetaTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\EntityAudit\Tests\MetaEntity',
        'SimpleThings\EntityAudit\Tests\MetaEntityCompositeId',
        'SimpleThings\EntityAudit\Tests\MetaNoMetaEntity',
    );

    protected $auditedEntities = array(
        'SimpleThings\EntityAudit\Tests\MetaEntity',
        'SimpleThings\EntityAudit\Tests\MetaEntityCompositeId',
        'SimpleThings\EntityAudit\Tests\MetaNoMetaEntity'
    );

    protected $metadataEnabledEntities = array(
        'SimpleThings\EntityAudit\Tests\MetaEntity',
        'SimpleThings\EntityAudit\Tests\MetaEntityCompositeId'
    );

    public function testHasTables()
    {
        $this->assertEquals(0, $this->em->getConnection()->fetchColumn('SELECT COUNT(*) FROM '.$this->auditManager->getConfiguration()->getRevisionMetaTableName()));
        $this->assertEquals(0, $this->em->getConnection()->fetchColumn('SELECT COUNT(*) FROM MetaEntity_meta'));
        $this->assertEquals(0, $this->em->getConnection()->fetchColumn('SELECT COUNT(*) FROM MetaEntityCompositeId_meta'));

        $this->setExpectedException('Doctrine\DBAL\DBALException', "An exception occurred while executing 'SELECT COUNT(*) FROM MetaEntityNoMetaEntity_meta'");
        $this->assertEquals(0, $this->em->getConnection()->fetchColumn('SELECT COUNT(*) FROM MetaEntityNoMetaEntity_meta'));
    }

    public function testMeta()
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $meta = new MetaEntity();
        $meta->setTitle('new meta entity');

        $this->auditManager->addRevisionMeta('foo', 'bar');
        $this->auditManager->addRevisionMeta('foo', 'buz');
        $this->auditManager->addRevisionMeta('boo', 'bar');

        //this is important to test before we have id
        $this->auditManager->addEntityMeta($meta, 'test', 'test data');

        $this->em->persist($meta);
        $this->em->flush(); //1

        $metaCI = new MetaEntityCompositeId();
        $metaCI->setId(42);
        $metaCI->setId2('other part of composite id');
        $metaCI->setTitle('new meta entity with composite keys');

        $this->auditManager->addRevisionMeta('magic', '42');

        $this->em->persist($metaCI);
        $this->em->flush(); //2

        $meta->setTitle('changed new meta entity');
        $metaCI->setTitle('changed new meta entity with composite keys');

        $this->auditManager->addEntityMeta($metaCI, 'key1', 'value1');
        $this->auditManager->addEntityMeta($metaCI, 'key2', 'value2');

        $this->em->flush(); //3

        $metaNM = new MetaNoMetaEntity();
        $metaNM->setTitle('new entity with no meta');

        $this->em->persist($metaNM);
        $this->em->flush(); //4

        //check revision metas
        $this->assertEquals(array('foo' => 'buz', 'boo' => 'bar'), $auditReader->getRevisionMeta('1'));
        $this->assertEquals(array('magic' => 42), $auditReader->getRevisionMeta(2));
        $this->assertEquals(array(), $auditReader->getRevisionMeta(3));
        $this->assertEquals(array(), $auditReader->getRevisionMeta('99'));

        $revision = $auditReader->findRevision(1);

        $this->assertEquals(array('foo' => 'buz', 'boo' => 'bar'), $revision->getMeta());
        $this->assertEquals('buz', $revision->getMeta('foo'));
        $this->assertEquals(null, $revision->getMeta('xyz'));

        $revisions = $auditReader->findRevisionHistory();

        $this->assertEquals(array('foo' => 'buz', 'boo' => 'bar'), $revisions[3]->getMeta());

        $revisions = $auditReader->findRevisions(get_class($meta), $meta->getId());

        $this->assertEquals(array('foo' => 'buz', 'boo' => 'bar'), $revisions[1]->getMeta());
        $this->assertEquals(array(), $revisions[0]->getMeta());

        $revisions = $auditReader->findRevisionsByMeta(array('foo' => 'buz'));
        $this->assertEquals(1, $revisions[0]->getRev());
        $this->assertCount(1, $revisions);

        $revisions = $auditReader->findRevisionsByMeta(array('foo' => 'buz', 'magic' => 42), false);
        $this->assertEquals(1, $revisions[0]->getRev());
        $this->assertEquals(2, $revisions[1]->getRev());
        $this->assertCount(2, $revisions);

        $revisions = $auditReader->findRevisionsByMeta('name = "magic" AND data = 42');
        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertCount(1, $revisions);

        $this->assertEquals(array(), $auditReader->getEntityMeta(get_class($metaCI), array('id' => $metaCI->getId(), 'id2' => $metaCI->getId2()), 4));
        $this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $auditReader->getEntityMeta(get_class($metaCI), array('id' => $metaCI->getId(), 'id2' => $metaCI->getId2()), 3));
        $this->assertEquals(array('test' => 'test data'), $auditReader->getEntityMeta(get_class($meta), $meta->getId(), 1));
    }
}

/** @ORM\Entity() */
class MetaEntity
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /** @ORM\Column(type="string") */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
}

/** @ORM\Entity() */
class MetaEntityCompositeId
{
    /** @ORM\Id @ORM\Column(type="integer", name="some_id") */
    private $id;

    /** @ORM\Id @ORM\Column(type="string", name="some_other_id") */
    private $id2;

    /** @ORM\Column(type="string") */
    private $title;

    public function getId2()
    {
        return $this->id2;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setId2($id2)
    {
        $this->id2 = $id2;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
}

/** @ORM\Entity() */
class MetaNoMetaEntity
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /** @ORM\Column(type="string") */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
}
