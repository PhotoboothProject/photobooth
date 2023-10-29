<?php

namespace Photobooth;

/**
 * Class FileDelete.
 */
class FileDelete
{
    /** @var string The filename of the file. */
    private $file;

    /** @var array The paths of the files to be deleted. */
    private $paths = [];

    /** @var array The file paths of the files that could not be deleted. */
    private $unavailableFiles = [];

    /** @var array The file paths of the files that were attempted to be deleted, but failed. */
    private $failedFiles = [];

    /** @var bool Whether or not the deletion of the filess was successful. */
    private $success = true;

    /**
     * FileDelete constructor.
     *
     * @param string $file The filename of the file.
     * @param array $paths The file paths of the files.
     */
    public function __construct($file, $paths)
    {
        $this->file = $file;
        $this->paths = $paths;
    }

    /**
     * Deletes the files.
     */
    public function deleteFiles()
    {
        foreach ($this->paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $this->file;
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
    }

    /**
     * Sets the paths.
     *
     * @param array $paths The file paths of the files.
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Sets the unavailable files.
     *
     * @param array $unavailableFiles The file paths of the unavailable files.
     */
    public function setUnavailableFiles(array $unavailableFiles)
    {
        $this->unavailableFiles = $unavailableFiles;
    }

    /**
     * Sets the failed files.
     *
     * @param array $failedFiles The file incl. paths of the failed files.
     */
    public function setFailedFiles(array $failedFiles)
    {
        $this->failedFiles = $failedFiles;
    }

    /**
     * Gets the log data.
     *
     * @return array The log data.
     */
    public function getLogData()
    {
        return [
            'success' => $this->success,
            'file' => $this->file,
            'unavailable' => $this->unavailableFiles,
            'failed' => $this->failedFiles,
        ];
    }
}
