<?php

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;

/**
 * Class DatabaseManager
 *
 * Manages the database, including adding and deleting files.
 */
class DatabaseManagerService
{
    public string $databaseFile = '';
    public string $imageDirectory = '';

    public function __construct()
    {
        $config = ConfigurationService::getInstance()->getConfiguration();
        $this->databaseFile = FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . $config['database']['file'] . '.txt';
        $this->imageDirectory = FolderEnum::IMAGES->absolute();
    }

    /**
     * Get the list of files from the database file.
     */
    public function getContentFromDB(): array
    {
        // check if the database file is defined and non-empty
        if (!isset($this->databaseFile) || empty($this->databaseFile)) {
            throw new \Exception('Database not defined.');
        }

        try {
            // get data from database
            if (file_exists($this->databaseFile)) {
                $data = file_get_contents($this->databaseFile);
                if ($data === false) {
                    throw new \Exception('Failed to read file: ' . $this->databaseFile);
                }
                $decodedData = json_decode($data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Failed to decode JSON: ' . json_last_error_msg());
                }

                return is_array($decodedData) ? $decodedData : [];
            } else {
                throw new \Exception('File not found: ' . $this->databaseFile);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return [];
    }

    /**
     * Get the list of images from the images directory.
     */
    public function getFilesFromDirectory(): array
    {
        // check if the directory is defined and non-empty
        if (!isset($this->imageDirectory) || empty($this->imageDirectory)) {
            throw new \Exception('Directory not defined.');
        }

        try {
            // open the directory
            $dh = opendir($this->imageDirectory);
            if ($dh === false) {
                throw new \Exception('Failed to open directory: ' . $this->imageDirectory);
            }

            // read the files in the directory
            $files = [];
            while (false !== ($filename = readdir($dh))) {
                $files[] = $filename;
            }
            closedir($dh);

            // filter the files to include only images with .jpg or .jpeg extensions
            $images = preg_grep('/\.(jpg|jpeg)$/i', $files);
            if ($images === false) {
                return [];
            }

            return $images;
        } catch (\Exception $e) {
            // do nothing
        }

        return [];
    }

    /**
     * Append a new content by name to the database file.
     */
    public function appendContentToDB(string $content): void
    {
        if (!$content) {
            throw new \Exception('Invalid content.');
        }

        // check if the database file is defined and non-empty
        if (!isset($this->databaseFile) || empty($this->databaseFile)) {
            throw new \Exception('Database not defined.');
        }

        $currContent = $this->getContentFromDB();

        if (!in_array($content, $currContent)) {
            $currContent[] = $content;
            file_put_contents($this->databaseFile, json_encode($currContent));
        }
    }

    /**
     * Delete an entry by name from the database file.
     */
    public function deleteContentFromDB(string $content): void
    {
        if (!$content) {
            throw new \Exception('Invalid filename.');
        }

        // check if the database file is defined and non-empty
        if (!isset($this->databaseFile) || empty($this->databaseFile)) {
            throw new \Exception('Database not defined.');
        }
        $currContent = $this->getContentFromDB();

        if (in_array($content, $currContent)) {
            unset($currContent[array_search($content, $currContent)]);
            file_put_contents($this->databaseFile, json_encode(array_values($currContent)));
        }

        if (file_exists($this->databaseFile) && empty($currContent)) {
            unlink($this->databaseFile);
        }
    }

    /**
     * Check if an content exists in the database file.
     */
    public function isInDB(string $content): bool
    {
        if (!$content) {
            throw new \Exception('Invalid filename.');
        }

        // check if the database file is defined and non-empty
        if (!isset($this->databaseFile) || empty($this->databaseFile)) {
            throw new \Exception('Database not defined.');
        }

        $currContent = $this->getContentFromDB();

        return in_array($content, $currContent);
    }

    /**
     * Returns the size of the database file in bytes.
     */
    public function getDBSize(): int
    {
        if (file_exists($this->databaseFile)) {
            return (int) filesize($this->databaseFile);
        }
        return 0;
    }

    /**
     * Rebuilds the image database by scanning the image directory and creating a new database
     * file with the names of all files sorted by modification time.
     *
     * @return string The string "success" if the database was rebuilt successfully, or "error"
     *                if an error occurred during the rebuilding process.
     */
    public function rebuildDB(): string
    {
        // check if the database file is defined and non-empty
        if (!isset($this->databaseFile) || empty($this->databaseFile)) {
            throw new \Exception('Database not defined.');
        }

        // check if the file directory is defined and non-empty
        if (!isset($this->imageDirectory) || empty($this->imageDirectory)) {
            throw new \Exception('File directory not defined.');
        }

        $output = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->imageDirectory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS)) as $value) {
            if ($value->isFile()) {
                $output[] = [$value->getMTime(), $value->getFilename()];
            }
        }

        if (!empty($output)) {
            usort($output, function ($a, $b) {
                return $a[0] <=> $b[0];
            });
        }

        try {
            $filenames = array_column($output, 1);
            $jsonData = json_encode($filenames);
            if ($jsonData === false) {
                throw new \Exception('Error: Failed to encode filenames to JSON.');
            }

            if (file_put_contents($this->databaseFile, $jsonData) === false) {
                throw new \Exception('Error: Failed to write data to database.');
            }

            return 'success';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
