<?php

declare(strict_types=1);

/*
 * This file is part of the bk2k/extension-helper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Photobooth\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigListCommand extends Command
{
    protected static $defaultName = 'photobooth:config:list';
    protected array $photoboothConfig = [];

    public function setPhotoboothConfig(array $photoboothConfig): self
    {
        $this->photoboothConfig = $photoboothConfig;
        return $this;
    }

    protected function configure()
    {
        $this->setDescription('Return the config as JSON');
        $this->setDefinition(
            new InputDefinition([
                new InputArgument('format', InputArgument::REQUIRED)
            ])
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $format = $input->getArgument('format');
        if ($format !== 'json') {
            $io->error('No valid format provided! Example: photobooth photobooth:config:list json');
            return 1;
        }

        echo json_encode($this->photoboothConfig);
        return 0;
    }
}
