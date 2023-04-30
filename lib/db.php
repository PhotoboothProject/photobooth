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
    private $db_file;

    /**
     * @var string The absolute path to the directory containing the files.
     */
    private $file_dir;

    /**
     * DatabaseManager constructor.
     *
     * Sets up the object with the necessary file and directory paths.
     */
    public function __construct($db_file, $file_dir) {
        if (!$db_file) {
            throw new InvalidArgumentExeption('Invalid database.');
        }
        $this->db_file = $db_file;
        if (!$file_dir) {
            throw new InvalidArgumentExeption('Invalid file path.');
        }
        $this->file_dir = $file_dir;
    }

    /**
     * Get the list of files from the database file.
     *
     * @return array The list of files from the database file.
     */
    public function getFilesFromDB() {
        // get data from database
        if (file_exists($this->db_file)) {
            return json_decode(file_get_contents($this->db_file));
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
     * Append a new file by filename to the database file.
     *
     * @param string $filename The filename of the file to add to the database file.
     */
    public function appendFileToDB($filename) {
        if (!$filename) {
            throw new InvalidArgumentExeption('Invalid filename.');
        }
        $files = $this->getFilesFromDB();

        if (!in_array($filename, $files)) {
            $files[] = $filename;
            file_put_contents($this->db_file, json_encode($files));
        }
    }

    /**
     * Delete an file by filename from the database file.
     *
     * @param string $filename The filename of the file to delete from the database file.
     */
    public function deleteFileFromDB($filename) {
        if (!$filename) {
            throw new InvalidArgumentExeption('Invalid filename.');
        }
        $files = $this->getFilesFromDB();

        if (in_array($filename, $files)) {
            unset($files[array_search($filename, $files)]);
            file_put_contents($this->db_file, json_encode(array_values($files)));
        }

        if (file_exists($this->db_file) && empty($files)) {
            unlink($this->db_file);
        }
    }

    /**
     * Check if an filename exists in the database file.
     *
     * @param string $filename The filename of the file to check.
     *
     * @return bool Whether the filename exists in the database file.
     */
    public function isFileInDB($filename) {
        if (!$filename) {
            throw new InvalidArgumentExeption('Invalid filename.');
        }
        $files = $this->getFilesFromDB();

        return in_array($filename, $files);
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
