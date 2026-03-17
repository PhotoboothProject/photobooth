<?php

declare(strict_types=1);

namespace Photobooth\Command;

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Service\UploadQueueService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'photobooth:upload:worker',
    description: 'Processes the async FTP/SFTP upload queue'
)]
class UploadWorkerCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Processes the async FTP/SFTP upload queue');
        $this->addOption('once', null, InputOption::VALUE_NONE, 'Process one job and exit');
        $this->addOption('poll-interval', null, InputOption::VALUE_REQUIRED, 'Seconds between queue polls', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = LoggerService::getInstance()->getLogger('uploadworker');
        $queue = UploadQueueService::getInstance();
        $once = (bool) $input->getOption('once');
        $pollInterval = (int) $input->getOption('poll-interval');

        $output->writeln('Upload worker started.');
        $logger->info('Upload worker started');

        // Reset any stale in_progress jobs from previous crashed runs
        $queue->resetStaleJobs();

        $setupDone = false;
        $lastConfigHash = '';

        while (true) {
            $job = $queue->fetchNext();

            if ($job === null) {
                if ($once) {
                    $output->writeln('No pending jobs, exiting.');

                    return Command::SUCCESS;
                }
                sleep($pollInterval);
                continue;
            }

            $jobId = (int) $job['id'];
            $imageFile = (string) $job['image_file'];
            $thumbFile = (string) $job['thumb_file'];
            $remoteFilename = (string) $job['remote_filename'];

            $output->writeln('Processing job #' . $jobId . ': ' . $imageFile . ' -> ' . $remoteFilename);
            $logger->info('Processing upload job', ['id' => $jobId, 'image' => $imageFile, 'remote' => $remoteFilename]);

            $queue->markInProgress($jobId);

            try {
                // Reload config from disk and create fresh RemoteStorageService per job
                // to pick up any admin panel config changes while worker is running
                ConfigurationService::getInstance()->load();
                $configHash = md5(serialize(ConfigurationService::getInstance()->getConfiguration()['ftp']));
                if ($configHash !== $lastConfigHash) {
                    $setupDone = false;
                    $lastConfigHash = $configHash;
                }

                $remoteStorage = new RemoteStorageService();

                // Setup remote directories and webpage once, retry on failure
                if (!$setupDone) {
                    $remoteStorage->ensureDirectoriesExist();
                    $remoteStorage->createWebpage();
                    $setupDone = true;
                    $logger->info('Remote setup completed.');
                }

                $imagePath = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $imageFile;
                $thumbPath = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $thumbFile;

                if (!file_exists($imagePath)) {
                    throw new \RuntimeException('Image file not found: ' . $imagePath);
                }

                $imageContents = file_get_contents($imagePath);
                if ($imageContents === false) {
                    throw new \RuntimeException('Failed to read image file: ' . $imagePath);
                }

                $remoteStorage->write($remoteStorage->getStoragePath('images/' . $remoteFilename), $imageContents);

                if (file_exists($thumbPath)) {
                    $thumbContents = file_get_contents($thumbPath);
                    if ($thumbContents !== false) {
                        $remoteStorage->write($remoteStorage->getStoragePath('thumbs/' . $remoteFilename), $thumbContents);
                    }
                }

                $queue->markCompleted($jobId);
                $logger->info('Upload completed', ['id' => $jobId, 'remote' => $remoteFilename]);
                $output->writeln('Job #' . $jobId . ' completed successfully.');
            } catch (\Throwable $e) {
                $setupDone = false;
                $queue->markFailed($jobId, $e->getMessage());
                $output->writeln('Job #' . $jobId . ' failed: ' . $e->getMessage());
                $logger->error('Upload job failed', ['id' => $jobId, 'error' => $e->getMessage()]);
            }

            if ($once) {
                return Command::SUCCESS;
            }
        }
    }
}
