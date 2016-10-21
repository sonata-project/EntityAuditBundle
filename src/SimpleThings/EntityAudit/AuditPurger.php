<?php
/**
 * Created by PhpStorm.
 * User: doconnell
 * Date: 20/10/16
 * Time: 10:24
 */

namespace SimpleThings\EntityAudit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\Exception\ConfigurationException;

/**
 * Class AuditPurger
 * Enable the purging of audit data based on the configured/ supplied retention period
 */
class AuditPurger
{
    /**
     * @var AuditManager
     */
    private $manager = null;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * AuditPurger constructor.
     * @param AuditManager $manager
     */
    public function __construct(AuditManager $manager, EntityManagerInterface $em)
    {
        $this->manager = $manager;
        $this->em = $em;
    }

    /**
     * Purge audit and revisions data prior to the configured or specified retention period
     * @param int $retentionPeriodOverride - override number of months to retain
     * @return bool - did we purge anything?
     */
    public function purge($retentionPeriodOverride = null)
    {
        $numRowsAffected = 0;
        $retentionPeriodOverride = AuditPurger::getValidPeriodValue($retentionPeriodOverride);
        $removeFromDate = $this->getPurgeDate($retentionPeriodOverride);
        if ($removeFromDate) {
            $config = $this->manager->getConfiguration();

            $revisionsTable = $config->getRevisionTableName();
            $revisionsJoinField = $config->getRevisionFieldName();

            $this->em->beginTransaction();

            // Delete audit table entries first
            foreach ($this->manager->getMetadataFactory()->getAllClassNames() as $audited) {
                $auditTable = $config->getTableName($this->em->getClassMetadata($audited));
                $numRowsAffected += $this->em->getConnection()->executeUpdate(
                    "DELETE FROM $auditTable 
                     WHERE $auditTable.$revisionsJoinField IN
                     (SELECT id FROM $revisionsTable WHERE timestamp < ?)",
                    array($removeFromDate->format('c'))
                );
            }
            // Now delete revisions entries
            $numRowsAffected += $this->em->getConnection()->executeUpdate(
                "DELETE FROM $revisionsTable WHERE timestamp < ?",
                array($removeFromDate->format('c'))
            );
            $this->em->commit();
        }
        return $numRowsAffected > 0;
    }

    /**
     * Return the date prior tio which data should be purged
     * @param null $retentionPeriodOverride
     * @return \DateTime|null
     */
    public function getPurgeDate($retentionPeriodOverride = null)
    {
        $removeFromDate = null;
        $config = $this->manager->getConfiguration();
        $retentionPeriod = $retentionPeriodOverride ?: $config->getRetentionPeriodMonths();
        if ($retentionPeriod) { // 0 or NULL = forever. Let's not be stupid and let 0 = "delete immediately"...
            $removeFromDate = new \DateTime('midnight first day of this month'); // Use "whole months"
            $removeFromDate->sub(new \DateInterval("P{$retentionPeriod}M"));
        }
        return $removeFromDate;
    }

    /**
     * Return the valid, sanitised config value or throw for invalid
     * @param $retentionPeriodMonths
     * @return int|null
     * @throws ConfigurationException
     */
    public static function getValidPeriodValue($retentionPeriodMonths)
    {
        if((!is_numeric(trim($retentionPeriodMonths)) && $retentionPeriodMonths !== null) || $retentionPeriodMonths < 0) {
            throw new ConfigurationException(
                'retentionPeriodMonths',
                $retentionPeriodMonths,
                'Retention period should be an integer with a value of 0 or greater'
            );
        }
        return $retentionPeriodMonths === null ? null : (int) trim($retentionPeriodMonths);
    }
}
