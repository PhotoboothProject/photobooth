<?php

namespace Photobooth;

use Photobooth\Utility\FileUtility;
use Photobooth\Utility\FontUtility;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\VideoUtility;

class FileUploader
{
    // The name of the folder where files will be uploaded
    private string $folderName;
    // Array of uploaded files ($_FILES)
    private array $uploadedFiles;
    // Path to the folder on the server
    private string $folderPath;
    // Supported MIME types for each folder
    private array $typeChecker;
    // Array to store general error messages
    private array $errors = [];
    // Array to store failed files and their errors
    private array $failedFiles = [];
    // Logger instance for debugging
    private Logger\NamedLogger $logger;

    public function __construct(string $folderName, array $uploadedFiles, Logger\NamedLogger $logger)
    {
        $this->folderName = $folderName;
        $this->uploadedFiles = $uploadedFiles;
        $this->logger = $logger;

        // Initialize supported MIME types for folders
        $this->typeChecker = [
            'private/images/background' => ImageUtility::supportedMimeTypesSelect,
            'private/images/frames' => ImageUtility::supportedMimeTypesSelect,
            'private/images/logo' => ImageUtility::supportedMimeTypesSelect,
            'private/images/placeholder' => ImageUtility::supportedMimeTypesSelect,
            'private/images/cheese' => ImageUtility::supportedMimeTypesSelect,
            'private/fonts' => FontUtility::supportedMimeTypesSelect,
            'private/videos/background' => VideoUtility::supportedMimeTypesSelect
        ];
    }

    public function uploadFiles(): array
    {
        $this->logger->debug('Received folderName', [$this->folderName]);

        if (!$this->isFolderValid()) {
            return $this->getResponse(false, 'Invalid folder name provided.', [], []);
        }

        if (!$this->prepareFolder()) {
            return $this->getResponse(false, 'Unable to write to the target folder.', [], []);
        }

        $uploadedFileNames = $this->processFiles();
        $success = empty($this->errors) && empty($this->failedFiles);

        return $this->getResponse(
            $success,
            $success ? 'Files uploaded successfully.' : 'Some files failed to upload.',
            $uploadedFileNames,
            $this->failedFiles
        );
    }

    private function isFolderValid(): bool
    {
        if (!isset($this->typeChecker[$this->folderName])) {
            $this->addError($this->folderName, 'The specified folder is not supported.');
            return false;
        }

        $this->folderPath = PathUtility::getAbsolutePath($this->folderName);
        return true;
    }

    private function prepareFolder(): bool
    {
        FileUtility::createDirectory($this->folderPath);

        if (!is_writable($this->folderPath)) {
            $this->addError($this->folderName, 'The target folder is not writable.');
            return false;
        }

        return true;
    }

    private function processFiles(): array
    {
        $uploadedFileNames = [];

        foreach ($this->uploadedFiles['name'] as $index => $fileName) {
            $fileError = $this->uploadedFiles['error'][$index];

            if ($fileError === UPLOAD_ERR_OK) {
                $fileTmpName = $this->uploadedFiles['tmp_name'][$index];
                $fileType = $this->uploadedFiles['type'][$index];
                $filePath = $this->folderPath . '/' . $fileName;

                $this->logger->debug('Processing file', [$fileName]);

                if ($this->validateFile($fileName, $fileType, $filePath)) {
                    $this->moveFile($fileTmpName, $filePath);
                    $uploadedFileNames[] = $fileName;
                }
            } else {
                $this->addError($fileName, $this->getFileErrorMessage($fileError));
            }
        }

        return $uploadedFileNames;
    }

    private function validateFile(string $fileName, string $fileType, string $filePath): bool
    {
        if (!in_array($fileType, $this->typeChecker[$this->folderName])) {
            $this->addError($fileName, 'Unsupported file type for the folder.');
            return false;
        }

        if (file_exists($filePath)) {
            $this->addError($fileName, 'The file already exists in the target folder.');
            return false;
        }

        return true;
    }

    private function moveFile(string $fileTmpName, string $filePath): void
    {
        if (move_uploaded_file($fileTmpName, $filePath)) {
            chmod($filePath, 0644);
            $this->logger->debug('File uploaded successfully', [$filePath]);
        } else {
            $this->addError(basename($filePath), 'Failed to move the file to the target folder.');
        }
    }

    private function addError(string $identifier, string $errorMessage): void
    {
        $this->errors[$identifier] = $errorMessage;
        $this->failedFiles[$identifier] = $errorMessage;
        $this->logger->debug($errorMessage, [$identifier]);
    }

    private function getResponse(bool $success, string $message, array $uploadedFileNames, array $failedFiles): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'errors' => $this->errors,
            'uploadedFiles' => $uploadedFileNames,
            'failedFiles' => $failedFiles
        ];
    }

    private function getFileErrorMessage(int $errorCode): string
    {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];

        return $errorMessages[$errorCode] ?? 'An unknown error occurred during the file upload.';
    }
}
