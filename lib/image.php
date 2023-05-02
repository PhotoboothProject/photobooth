<?php

class Image {
    /**
     * @var string The new filename for the image.
     */
    public $newFilename;

    /**
     * @var int $jpegQuality The quality of the saved jpeg image, from 0 (lowest) to 100 (highest). Default is 80.
     */
    public $jpegQuality = 80;

    /**
     * Creates a new filename for the image.
     *
     * @param string $naming The naming convention to use for the filename. Options are "random" or "dateformatted".
     * @param string $ext The file extension to use for the filename. Default is ".jpg".
     * @return string The new filename.
     */
    public static function create_new_filename($naming = 'random', $ext = '.jpg') {
        if ($naming === 'dateformatted') {
            $name = date('Ymd_His') . $ext;
        } else {
            $name = md5(microtime()) . $ext;
        }
        return $name;
    }

    /**
     * Sets the new filename for the image using the specified naming convention.
     *
     * @param string $naming The naming convention to use for the filename. Options are "random" or "dateformatted".
     */
    public function set_new_filename($naming) {
        $this->newFilename = $this->create_new_filename($naming);
    }

    /**
     * Returns the new filename for the image.
     *
     * @return string The new filename.
     */
    public function get_new_filename() {
        return $this->newFilename;
    }

    /**
     * Sets the new filename for the image using the specified naming convention and returns the new filename.
     *
     * @param string $naming The naming convention to use for the filename. Options are "random" or "dateformatted".
     * @return string The new filename.
     */
    public function set_and_get_new_filename($naming) {
        $this->set_new_filename($naming);
        return $this->newFilename;
    }

    /**
     * Creates a GD image resource from an image file.
     *
     * @param string $image The file path or URL of the image to create a resource from.
     * @return resource|false Returns the GD image resource if successful, or false if an error occurs.
     */
    public static function createFromImage($image) {
        try {
            $resource = imagecreatefromstring(file_get_contents($image));
            if (!$resource) {
                throw new Exception('Can\'t create GD resource.');
            }
            return $resource;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validates a GD image resource.
     *
     * @param resource|null $resource The GD image resource to validate.
     * @return bool Returns true if the resource is a valid GD image resource, or false otherwise.
     */
    private function validateGdResource($resource) {
        try {
            if (!isset($resource) || !is_resource($resource) || get_resource_type($resource) !== 'gd') {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Saves a GD image resource to disk.
     *
     * @param resource $sourceResource The GD image resource to save.
     * @param string $destination The file path and name where the image will be saved.
     *
     * @return bool Returns true on success, or false on failure.
     */
    public function saveJpeg($sourceResource, $destination) {
        try {
            // Check if the $sourceResource and $destination are defined
            if (!isset($sourceResource) || !isset($destination)) {
                throw new Exception('Missing parameters.');
            }

            // Validate the GD resource
            if (!$this->validateGdResource($sourceResource)) {
                throw new Exception('Invalid GD resource.');
            }

            // Save the image to disk
            if (!imagejpeg($sourceResource, $destination, $this->jpegQuality)) {
                throw new Exception('Error saving image.');
            }

            return true;
        } catch (Exception $e) {
            // If there is an exception, return false
            return false;
        }
    }
}
