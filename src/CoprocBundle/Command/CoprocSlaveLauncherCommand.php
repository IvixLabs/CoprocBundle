<?php
namespace IvixLabs\CoprocBundle\Command;

use IvixLabs\CoprocBundle\Factory\SlaveFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CoprocSlaveLauncherCommand extends Command
{
    /**
     * @var SlaveFactory
     */
    private $slaveFactory;

    public function __construct(SlaveFactory $slaveFactory)
    {
        parent::__construct();
        $this->slaveFactory = $slaveFactory;
    }

    protected function configure()
    {
        $this->setName('ivixlabs:coproc:slave-launcher')
            ->setDescription('Launch coproc slaves')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slaveName = $input->getArgument('name');
        $slave = $this->slaveFactory->createSlave($slaveName);
        $slave->listen();
    }
}