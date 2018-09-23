<?php


namespace App\Form;

use Symfony\Component\Validator\Constraints as Assert;


class UserRegistrationForm
{

    private $username;
    private $password;
    private $email;

    /**
     * @return mixed
     * @Assert\NotBlank()
     * @Assert\Length(min=4,max=16)
     *
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return UserRegistrationForm
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return mixed
     * @Assert\NotBlank()
     * @Assert\Length(min=6,max=32)
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     * @return UserRegistrationForm
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return mixed
     * @Assert\NotBlank()
     * @Assert\Email( checkMX = true)
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return UserRegistrationForm
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }



}