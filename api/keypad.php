<?php
class Keypad {
    public function __construct() {
    }

    public function keypadLogin($userPin, $pin) {
        if ($userPin == $pin) {
            $_SESSION['auth'] = true;

            $return = [
                'state' => true,
            ];
        } else {
            $return = [
                'state' => false,
            ];
        }

        return $return;
    }
}

?>
