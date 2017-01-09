<?php

/**
 * Created by PhpStorm.
 * User: doconnell
 * Date: 21/10/16
 * Time: 11:27
 */
namespace SimpleThings\EntityAudit\Command;

use SimpleThings\EntityAudit\AuditPurger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeCommand extends ContainerAwareCommand
{

    const RETENTION_PERIOD = 'retention-period';

    protected function configure()
    {
        $this
            ->setName('audit:purge')
            ->setDescription('Purge the audit data based on configured/ user-specified retention period')
            ->setHelp("This command enables you to purge audit data older then the specified retention period")
            ->addArgument(
                self::RETENTION_PERIOD,
                InputArgument::OPTIONAL,
                'The number of months you want to retain (e.g. anything older than that will be deleted)'
            )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $retentionPeriod = $input->getArgument(self::RETENTION_PERIOD);
        /** @var AuditPurger $purger */
        $purger = $this->getContainer()->get('simplethings_entityaudit.purger');
        $purged = $purger->purge($retentionPeriod);
        if ($purged) {
            $deleteBefore = $purger->getPurgeDate($retentionPeriod);
            $output->writeln('Purged audit data prior to '.$deleteBefore->format('jS F Y'));
        } else {
            $output->writeln('Nothing to purge');
        }
    }

}
