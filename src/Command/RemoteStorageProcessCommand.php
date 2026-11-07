<?php

declare(strict_types=1);

namespace Photobooth\Command;

use Photobooth\Service\ConfigurationService;
use Photobooth\Service\RemoteStorageQueueService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'photobooth:remote-storage:process',
    description: 'Uploads pictures queued for the remote storage (FTP/SFTP)'
)]
class RemoteStorageProcessCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('status', null, InputOption::VALUE_NONE, 'Only list the queued pictures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queue = RemoteStorageQueueService::getInstance();

        if ($input->getOption('status')) {
            $jobs = $queue->getJobs();
            if ($jobs === []) {
                $io->success('Upload queue is empty.');
                return Command::SUCCESS;
            }
            $io->table(
                ['File', 'Attempts', 'Queued at'],
                array_map(fn (array $job) => [$job['file'], $job['attempts'], date('Y-m-d H:i:s', $job['created'])], $jobs)
            );
            return Command::SUCCESS;
        }

        $config = ConfigurationService::getInstance()->getConfiguration();
        if (!$config['ftp']['enabled']) {
            $io->note('Remote storage is disabled, nothing to do.');
            return Command::SUCCESS;
        }

        $result = $queue->process(function (string $file, bool $success, ?string $error) use ($io): void {
            if ($success) {
                $io->writeln('<info>Uploaded</info> ' . $file);
            } else {
                $io->writeln('<error>Failed</error> ' . $file . ': ' . $error);
            }
        });

        if ($result['locked']) {
            $io->note('Another upload worker is already running.');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Uploaded: %d, failed: %d, remaining: %d', $result['uploaded'], $result['failed'], $result['remaining']));

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
