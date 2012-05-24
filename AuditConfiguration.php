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

namespace SimpleThings\EntityAudit;

class AuditConfiguration
{
    private $tablePrefix = '';
    private $tableSuffix = '_audit';
    private $revisionTableName = 'revisions';
    private $revisionIdFieldName = 'id';
    private $revisionIdFieldType = 'integer';
    private $revisionTimestampFieldName = 'timestamp';
    private $revisionUsernameFieldName = 'username';
    private $revisionSequenceName = 'revisions_seq';
    private $histRevisionFieldName = 'rev';
    private $histTypeFieldName = 'revtype';
    private $auditedEntityClasses = array();
    private $currentUsername = '';


    public function getTablePrefix()
    {
        return $this->prefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getTableSuffix()
    {
        return $this->suffix;
    }

    public function setTableSuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
    }

    public function setAuditedEntityClasses(array $classes)
    {
        $this->auditedEntityClasses = $classes;
    }

    public function createMetadataFactory()
    {
        return new Metadata\MetadataFactory($this->auditedEntityClasses);
    }

    public function setCurrentUsername($username)
    {
        $this->currentUsername = $username;
    }

    public function getCurrentUsername()
    {
        return $this->currentUsername;
    }

    public function setRevisionIdFieldType($revisionIdFieldType)
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    public function getRevisionIdFieldType()
    {
        return $this->revisionIdFieldType;
    }

    public function setHistRevisionFieldName($histRevisionFieldName)
    {
        $this->histRevisionFieldName = $histRevisionFieldName;
    }

    public function getHistRevisionFieldName()
    {
        return $this->histRevisionFieldName;
    }

    public function setHistTypeFieldName($histTypeFieldName)
    {
        $this->histTypeFieldName = $histTypeFieldName;
    }

    public function getHistTypeFieldName()
    {
        return $this->histTypeFieldName;
    }

    public function setRevisionIdFieldName($revisionIdFieldName)
    {
        $this->revisionIdFieldName = $revisionIdFieldName;
    }

    public function getRevisionIdFieldName()
    {
        return $this->revisionIdFieldName;
    }

    public function setRevisionSequenceName($revisionSequenceName)
    {
        $this->revisionSequenceName = $revisionSequenceName;
    }

    public function getRevisionSequenceName()
    {
        return $this->revisionSequenceName;
    }

    public function setRevisionTimestampFieldName($revisionTimestampFieldName)
    {
        $this->revisionTimestampFieldName = $revisionTimestampFieldName;
    }

    public function getRevisionTimestampFieldName()
    {
        return $this->revisionTimestampFieldName;
    }

    public function setRevisionUsernameFieldName($revisionUsernameFieldName)
    {
        $this->revisionUsernameFieldName = $revisionUsernameFieldName;
    }

    public function getRevisionUsernameFieldName()
    {
        return $this->revisionUsernameFieldName;
    }
}
