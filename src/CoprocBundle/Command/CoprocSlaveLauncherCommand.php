<?php
namespace IvixLabs\CoprocBundle\Command;

use IvixLabs\CoprocBundle\Factory\CoprocFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoprocSlaveLauncherCommand extends Command
{

    /**
     * @var CoprocFactory
     */
    private $coprocFactory;

    protected function configure()
    {
        $this->setName('ivixlabs:coproc:slave-launcher')
            ->setDescription('Launch copro slaves')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slaveName = $input->getArgument('name');
        $slave = $this->coprocFactory->getSlave($slaveName);
        $slave->listen();
    }

    public function setCoprocFactory(CoprocFactory $coprocFactory)
    {
        $this->coprocFactory = $coprocFactory;
    }
}