<?php
/**
 * Date: 18/09/2018
 * Time: 4:00
 */

namespace App\Auth;


use App\Entity\Entity;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends Entity implements UserInterface, \JsonSerializable
{
    private $username;
    private $password;
    private $email;
    private $enabled;
    private $accountNonExpired;
    private $credentialsNonExpired;
    private $accountNonLocked;
    private $roles;
    private $id;
    private $google_id;
    private $twitter_id;

    public function __construct(?string $username, ?string $password, array $roles = ['ROLE_USER'], bool $enabled = true,
                                bool $accountNonExpired = true, bool $credentialsNonExpired = true,
                                bool $accountNonLocked = true, string $email = null, array $data = [])
    {
        if ('' === $username || null === $username) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->username = $username;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->accountNonExpired = $accountNonExpired;
        $this->credentialsNonExpired = $credentialsNonExpired;
        $this->accountNonLocked = $accountNonLocked;
        $this->roles = $roles;
        $this->email = $email;
        parent::__construct($data);
    }

    public static function toArray(User $user){
        return get_object_vars($user);
    }

    public static function fromArray(array $entity)
    {
        $user = new User('hola','ey');
        foreach ($entity as $key=>$value) {
            if($value instanceof BSONArray or $value instanceof BSONDocument) {
                $value = iterator_to_array($value);
            }
            $keyAux = ucfirst($key);
            $method = "set{$keyAux}";
            if(method_exists($user, $method)) {
                $user->{$method}($value);
            }
        }

        return $user;
    }


    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return $this->accountNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return $this->accountNonLocked;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return $this->credentialsNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        if ($this->isAccountNonExpired() !== $user->isAccountNonExpired()) {
            return false;
        }

        if ($this->isAccountNonLocked() !== $user->isAccountNonLocked()) {
            return false;
        }

        if ($this->isCredentialsNonExpired() !== $user->isCredentialsNonExpired()) {
            return false;
        }

        if ($this->isEnabled() !== $user->isEnabled()) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @param null|string $password
     * @return User
     */
    public function setPassword(?string $password): User
    {
        $this->password = $password;
        return $this;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @param array|null $vars
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(?array $vars = null)
    {
        $forbidden = ['password', 'google_id', 'twitter_id'];
        $vars = $vars ?? get_object_vars($this);
        $result = [];
        foreach ($vars as $key=>$value){
            if(!in_array($key, $forbidden)){
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTwitterId()
    {
        return $this->twitter_id;
    }

    /**
     * @param mixed $twitter_id
     */
    public function setTwitterId($twitter_id): void
    {
        $this->twitter_id = $twitter_id;
    }

    /**
     * @return mixed
     */
    public function getGoogleId()
    {
        return $this->google_id;
    }

    /**
     * @param mixed $google_id
     */
    public function setGoogleId($google_id): void
    {
        $this->google_id = $google_id;
    }

    /**
     * @param null|string $username
     * @return User
     */
    public function setUsername(?string $username): User
    {
        $this->username = $username;
        return $this;
    }


}