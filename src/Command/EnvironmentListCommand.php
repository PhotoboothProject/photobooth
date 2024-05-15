<?php

declare(strict_types=1);

namespace Photobooth\Command;

use Photobooth\Environment;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'photobooth:environment:list', description: 'Returns the Photobooth environment as JSON')]
class EnvironmentListCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Return the config as JSON');
        $this->setDefinition(
            new InputDefinition([
                new InputArgument('format', InputArgument::REQUIRED)
            ])
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $format = $input->getArgument('format');
        if ($format !== 'json') {
            $io->error('No valid format provided! Example: photobooth photobooth:environment:list json');
            return 1;
        }

        echo json_encode((new Environment()));
        return 0;
    }
}
