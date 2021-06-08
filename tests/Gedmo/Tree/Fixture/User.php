<?php

namespace Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="user")
 */
class User extends Role
{
    const PASSWORD_SALT = 'dfJko$~346958rg!DFT]AEtzserf9giq)3/TAeg;aDFa43';

    /**
     * @ORM\Column(name="email", type="string", unique=true)
     *
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="password_hash", type="string", length=32)
     *
     * @var string
     */
    private $passwordHash;

    /**
     * @ORM\Column(name="activation_code", type="string", length=12)
     *
     * @var string
     */
    private $activationCode;

    /**
     * @param string $email
     * @param string $password
     */
    public function __construct($email, $password)
    {
        parent::__construct();
        $this
      ->setEmail($email)
      ->setPassword($password);
    }

    public function init()
    {
        $this->setActivationCode($this->generateString(12));
    }

    /**
     * Generates a random password
     *
     * @param int $length
     *
     * @return string
     */
    public function generateString($length = 8)
    {
        $length = (int) $length;
        if ($length < 0) {
            throw new \Exception("Invalid password length '$length'");
        }
        $set = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = strlen($set);
        $ret = '';
        for ($i = 0; $i < $length; ++$i) {
            $ret .= $set[random_int(0, $num - 1)];
        }

        return $ret;
    }

    /**
     * Generates a password hash
     *
     * @param string $password
     *
     * @return string
     */
    public function generatePasswordHash($password)
    {
        return md5($password.self::PASSWORD_SALT);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
        $this->setRoleId($email);

        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->passwordHash = $this->generatePasswordHash(trim($password));

        return $this;
    }

    /**
     * @return string
     */
    public function getActivationCode()
    {
        return $this->activationCode;
    }

    /**
     * @param string $activationCode
     *
     * @return User
     */
    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;

        return $this;
    }
}
