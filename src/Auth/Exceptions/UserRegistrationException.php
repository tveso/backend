<?php
/**
 * Date: 09/11/2018
 * Time: 16:41
 */

namespace App\Auth\Exceptions;


use Exception;
use Throwable;

class UserRegistrationException extends Exception
{
    private $errors = [];

    public function addError(string $errors) {
        $this->errors[] = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}