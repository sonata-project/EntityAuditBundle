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
    private $prefix = '';
    private $suffix = '_audit';
    private $revisionFieldName = 'rev';
    private $revisionTypeFieldName = 'revtype';
    private $revisionDiffFieldName = 'diff';
    private $revisionTableName = 'revisions';
    private $auditedEntityClasses = array();
    private $currentUsername = '';
    private $revisionIdFieldType = 'integer';
    private $revisionDescriptionFieldName = 'description';
    private $revisionDescriptionFieldType = 'text';
    private $currentDescription = '';
    private $revisionProcessedFieldName = 'processed_at';
    private $revisionProcessedFieldType = 'datetime';

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

    public function getRevisionFieldName()
    {
        return $this->revisionFieldName;
    }

    public function setRevisionFieldName($revisionFieldName)
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    public function setRevisionTypeFieldName($revisionTypeFieldName)
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    public function getRevisionDiffFieldName()
    {
        return $this->revisionDiffFieldName;
    }

    public function setRevisionDiffFieldName($revisionDiffFieldName)
    {
        $this->revisionDiffFieldName = $revisionDiffFieldName;
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

    public function getRevisionDescriptionFieldName()
    {
        return $this->revisionDescriptionFieldName;
    }

    public function setRevisionDescriptionFieldName($revisionDescriptionFieldName)
    {
        $this->revisionDescriptionFieldName = $revisionDescriptionFieldName;
    }

    public function getRevisionDescriptionFieldType()
    {
        return $this->revisionDescriptionFieldType;
    }

    public function setRevisionDescriptionFieldType($revisionDescriptionFieldType)
    {
        $this->revisionDescriptionFieldType = $revisionDescriptionFieldType;
    }

    public function setCurrentDescription($description)
    {
        $this->currentDescription = $description;
    }
    
    public function getCurrentDescription()
    {
        return $this->currentDescription;
    }

    public function getRevisionProcessedFieldName()
    {
        return $this->revisionProcessedFieldName;
    }

    public function setRevisionProcessedFieldName($revisionProcessedFieldName)
    {
        $this->revisionProcessedFieldName = $revisionProcessedFieldName;
    }

    public function getRevisionProcessedFieldType()
    {
        return $this->revisionProcessedFieldType;
    }

    public function setRevisionProcessedFieldType($revisionProcessedFieldType)
    {
        $this->revisionProcessedFieldType = $revisionProcessedFieldType;
    }
}
