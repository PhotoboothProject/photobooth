<?php
class Keypad {
    public function __construct() {
    }

    public function keypadLogin($userPin, $login) {
        if ($userPin == $login['pin']) {
            $_SESSION['auth'] = true;

            $return = [
                'state' => true,
            ];
        } elseif ($login['rental_keypad'] && $userPin == $login['rental_pin']) {
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
