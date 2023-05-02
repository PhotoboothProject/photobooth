<?php

class Image {
    /**
     * @var string The new filename for the image.
     */
    public $newFilename;

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
}

