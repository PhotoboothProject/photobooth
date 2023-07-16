<?php
class Keypad {
    public function __construct() {
    }

    public function keypadLogin($userPin, $config) {
        if ($userPin == $config['login']['pin']) {
            $_SESSION['auth'] = true;

            $return = [
                'state' => true,
            ];
        } elseif ($config['login']['rental_keypad'] && $userPin == $config['login']['rental_pin']) {
            $_SESSION['rental'] = true;

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
