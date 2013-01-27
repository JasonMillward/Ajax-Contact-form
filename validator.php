<?php
class Validator {
    private $errorString;

    public function isValid($var, $type, $name = NULL) {
        switch($type) {

            case "email":
                    return $this->sanitiseEmail($var);
                break;
            case "string":
                    return $this->sanitiseString($var,$name);
                break;
            case "text":
                    return $this->sanitiseText($var,$name);
                break;
            default:
                return false;
                break;
        }
    }

    public function getError() {
        return $this->errorString;
    }

    private function sanitiseEmail($email) {

        $email = strtolower($email);

        if (empty($email)) {
            $this->errorString = 'Email must not be empty';
            return false;
        }

        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            $this->errorString = 'Email is not valid';
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function sanitiseText($string, $name) {

        $string = filter_var($string,FILTER_SANITIZE_STRING);

        if ( empty($string) ) {
            $this->errorString = $name . ' must not be empty';
            return false;
        }

        return $string;
    }

    private function sanitiseString($string, $name) {

        $string = filter_var($string,FILTER_SANITIZE_STRING);
        $string = str_replace( array("\r","\n",":"), array(" "," "," "), $string );

        if ( empty($string) ) {
            $this->errorString = $name . ' must not be empty';
            return false;
        }

        if ( strlen($string) > 70 ) {
            $this->errorString = $name .  ' is too long.';
            return false;
        }

        return $string;
    }

}