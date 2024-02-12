<?php

namespace Photobooth;

/**
 * Class FileDelete.
 */
class FileDelete
{
    /** @var string The filename of the file. */
    private string $file;

    /** @var array The paths of the files to be deleted. */
    private array $paths = [];

    /** @var array The file paths of the files that could not be deleted. */
    private array $unavailableFiles = [];

    /** @var array The file paths of the files that were attempted to be deleted, but failed. */
    private array $failedFiles = [];

    /** @var bool Whether the deletion of the filess was successful. */
    private bool $success = true;

    private array $test = [];

    /**
     * FileDelete constructor.
     *
     * @param string $file The filename of the file.
     * @param array $paths The file paths of the files.
     */
    public function __construct(string $file, array $paths)
    {
        $this->file = $file;
        $this->paths = $paths;
    }

    /**
     * @return void
     *
     * Deletes the files.
     */
    public function deleteFiles(): void
    {
        foreach ($this->paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $this->file;
            $this->doDelete($file);
            $this->deleteNamedImage();
        }
    }

    /**
     * @return void
     *
     * Delete named images
     */
    private function deleteNamedImage(): void
    {
        $fileName =  $this->file;
        $fileListToDelete = [];
        foreach ($this->paths as $path) {
            $files = scandir($path);
            foreach ($files as $file) {
                if(str_contains($file, $fileName)) {
                    $fileListToDelete[] = $path . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
        foreach ($fileListToDelete as $file) {
            $this->doDelete($file);
        }
    }

    /**
     * @param $file
     * @return void
     *
     * Actually delete the image
     */
    private function doDelete($file): void
    {
        try {
            if (is_readable($file)) {
                if (!unlink($file)) {
                    $this->success = false;
                    $this->failedFiles[] = $file;
                }
            } else {
                $this->unavailableFiles[] = $file;
            }
        } catch (\Exception $e) {
            $this->success = false;
            $this->failedFiles[] = $file;
        }
    }

    /**
     * Sets the paths.
     *
     * @param array $paths The file paths of the files.
     */
    public function setPaths(array $paths): void
    {
        $this->paths = $paths;
    }

    /**
     * Sets the unavailable files.
     *
     * @param array $unavailableFiles The file paths of the unavailable files.
     */
    public function setUnavailableFiles(array $unavailableFiles): void
    {
        $this->unavailableFiles = $unavailableFiles;
    }

    /**
     * Sets the failed files.
     *
     * @param array $failedFiles The file incl. paths of the failed files.
     */
    public function setFailedFiles(array $failedFiles): void
    {
        $this->failedFiles = $failedFiles;
    }

    /**
     * Gets the log data.
     *
     * @return array The log data.
     */
    public function getLogData(): array
    {
        return [
            'success' => $this->success,
            'file' => $this->file,
            'unavailable' => $this->unavailableFiles,
            'failed' => $this->failedFiles,
            'test' => $this->test
        ];
    }
}
