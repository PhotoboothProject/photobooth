<?php
require_once __DIR__ . '/config.php';

define('DB_FILE', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['database']['file'] . '.txt');
define('MAIL_FILE', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['mail']['file'] . '.txt');
define('IMG_DIR', $config['foldersAbs']['images']);

/**
 * Class DatabaseManager
 *
 * Manages the database, including adding and deleting files.
 */
class DatabaseManager {
    /**
     * @var string The absolute path and name of the file containing the database of files.
     */
    public $db_file = '';

    /**
     * @var string The absolute path to the directory containing the files.
     */
    public $file_dir = '';

    /**
     * Get the list of files from the database file.
     *
     * @return array The list of files from the database file.
     */
    public function getContentFromDB() {
        // check if the database file is defined and non-empty
        if (!isset($this->db_file) || empty($this->db_file)) {
            throw new Exception('Database not defined.');
        }

        try {
            // get data from database
            if (file_exists($this->db_file)) {
                $data = file_get_contents($this->db_file);
                if ($data === false) {
                    throw new Exception('Failed to read file: ' . $this->db_file);
                }
                return json_decode($data);
            } else {
                throw new Exception('File not found: ' . $this->db_file);
            }
        } catch (Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * Get the list of images from the images directory.
     *
     * @return array The list of images from the images directory.
     */
    public function getFilesFromDirectory() {
        $dh = opendir($this->file_dir);

        while (false !== ($filename = readdir($dh))) {
            $files[] = $filename;
        }
        closedir($dh);
        $images = preg_grep('/\.(jpg|jpeg|JPG|JPEG)$/i', $files);
        return $images;
    }

    /**
     * Append a new content by name to the database file.
     *
     * @param string $content The content to add to the database file.
     */
    public function appendContentToDB($content) {
        if (!$content) {
            throw new InvalidArgumentException('Invalid content.');
        }

        // check if the database file is defined and non-empty
        if (!isset($this->db_file) || empty($this->db_file)) {
            throw new Exception('Database not defined.');
        }

        $currContent = $this->getContentFromDB();

        if (!in_array($content, $currContent)) {
            $currContent[] = $content;
            file_put_contents($this->db_file, json_encode($currContent));
        }
    }

    /**
     * Delete an content by name from the database file.
     *
     * @param string $content The content to delete from the database file.
     */
    public function deleteContentFromDB($content) {
        if (!$content) {
            throw new InvalidArgumentException('Invalid filename.');
        }

        // check if the database file is defined and non-empty
        if (!isset($this->db_file) || empty($this->db_file)) {
            throw new Exception('Database not defined.');
        }
        $currContent = $this->getContentFromDB();

        if (in_array($content, $currContent)) {
            unset($currContent[array_search($content, $currContent)]);
            file_put_contents($this->db_file, json_encode(array_values($currContent)));
        }

        if (file_exists($this->db_file) && empty($currContent)) {
            unlink($this->db_file);
        }
    }

    /**
     * Check if an content exists in the database file.
     *
     * @param string $content The content of the file to check.
     *
     * @return bool Whether the content exists in the database file.
     */
    public function isInDB($content) {
        if (!$content) {
            throw new InvalidArgumentException('Invalid filename.');
        }

        // check if the database file is defined and non-empty
        if (!isset($this->db_file) || empty($this->db_file)) {
            throw new Exception('Database not defined.');
        }

        $currContent = $this->getContentFromDB();

        return in_array($content, $currContent);
    }

    /**
     * Returns the size of the database file.
     *
     * @return int The size of the database file in bytes.
     */
    public function getDBSize() {
        if (file_exists($this->db_file)) {
            return (int) filesize($this->db_file);
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
    public function rebuildDB() {
        // check if the database file is defined and non-empty
        if (!isset($this->db_file) || empty($this->db_file)) {
            throw new Exception('Database not defined.');
        }

        // check if the file directory is defined and non-empty
        if (!isset($this->file_dir) || empty($this->file_dir)) {
            throw new Exception('File directory not defined.');
        }

        $output = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->file_dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS)) as $value) {
            if ($value->isFile()) {
                $output[] = [$value->getMTime(), $value->getFilename()];
            }
        }

        usort($output, function ($a, $b) {
            return strlen($a[0]) <=> strlen($b[0]);
        });

        if (file_put_contents($this->db_file, json_encode(array_column($output, 1))) === 'false') {
            return 'error';
        } else {
            return 'success';
        }
    }
}
