<?php

/**
 * Created by PhpStorm.
 * User: doconnell
 * Date: 21/10/16
 * Time: 11:27
 */
namespace SimpleThings\EntityAudit\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('audit:purge')
            ->setDescription('Purge the audit data based on configured/ user-specified retention period')
            ->setHelp("This command enables you to purge audit data older then the specified retention period")
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $purger = $this->getContainer()->get('simplethings_entityaudit.purger');
        // ...

        // access the container using getContainer()
        $userManager = $this->getContainer()->get('app.user_manager');
        $userManager->create($input->getArgument('username'));

        $output->writeln('User successfully generated!');
    }

}
