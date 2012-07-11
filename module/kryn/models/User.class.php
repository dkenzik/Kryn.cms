<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * User
 */
class User
{
    /**
     * @var string $username
     */
    private $username;

    /**
     * @var string $auth_class
     */
    private $auth_class;

    /**
     * @var string $passwd
     */
    private $passwd;

    /**
     * @var string $passwd_salt
     */
    private $passwd_salt;

    /**
     * @var string $activationkey
     */
    private $activationkey;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var text $desktop
     */
    private $desktop;

    /**
     * @var text $settings
     */
    private $settings;

    /**
     * @var integer $created
     */
    private $created;

    /**
     * @var integer $modified
     */
    private $modified;

    /**
     * @var boolean $activate
     */
    private $activate;

    /**
     * @var string $first_name
     */
    private $first_name;

    /**
     * @var string $last_name
     */
    private $last_name;

    /**
     * @var int $sex
     */
    private $sex;

    /**
     * @var int $logins
     */
    private $logins;

    /**
     * @var int $lastlogin
     */
    private $lastlogin;

    /**
     * @var integer $rsn
     */
    private $rsn;


    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set auth_class
     *
     * @param string $authClass
     * @return User
     */
    public function setAuthClass($authClass)
    {
        $this->auth_class = $authClass;
        return $this;
    }

    /**
     * Get auth_class
     *
     * @return string 
     */
    public function getAuthClass()
    {
        return $this->auth_class;
    }

    /**
     * Set passwd
     *
     * @param string $passwd
     * @return User
     */
    public function setPasswd($passwd)
    {
        $this->passwd = $passwd;
        return $this;
    }

    /**
     * Get passwd
     *
     * @return string 
     */
    public function getPasswd()
    {
        return $this->passwd;
    }

    /**
     * Set passwd_salt
     *
     * @param string $passwdSalt
     * @return User
     */
    public function setPasswdSalt($passwdSalt)
    {
        $this->passwd_salt = $passwdSalt;
        return $this;
    }

    /**
     * Get passwd_salt
     *
     * @return string 
     */
    public function getPasswdSalt()
    {
        return $this->passwd_salt;
    }

    /**
     * Set activationkey
     *
     * @param string $activationkey
     * @return User
     */
    public function setActivationkey($activationkey)
    {
        $this->activationkey = $activationkey;
        return $this;
    }

    /**
     * Get activationkey
     *
     * @return string 
     */
    public function getActivationkey()
    {
        return $this->activationkey;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set desktop
     *
     * @param text $desktop
     * @return User
     */
    public function setDesktop($desktop)
    {
        $this->desktop = $desktop;
        return $this;
    }

    /**
     * Get desktop
     *
     * @return text 
     */
    public function getDesktop()
    {
        return $this->desktop;
    }

    /**
     * Set settings
     *
     * @param text $settings
     * @return User
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Get settings
     *
     * @return text 
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Get created
     *
     * @return integer 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param integer $modified
     * @return User
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
        return $this;
    }

    /**
     * Get modified
     *
     * @return integer 
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set activate
     *
     * @param boolean $activate
     * @return User
     */
    public function setActivate($activate)
    {
        $this->activate = $activate;
        return $this;
    }

    /**
     * Get activate
     *
     * @return boolean 
     */
    public function getActivate()
    {
        return $this->activate;
    }

    /**
     * Set first_name
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;
        return $this;
    }

    /**
     * Get first_name
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->last_name = $lastName;
        return $this;
    }

    /**
     * Get last_name
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set sex
     *
     * @param int $sex
     * @return User
     */
    public function setSex(\int $sex)
    {
        $this->sex = $sex;
        return $this;
    }

    /**
     * Get sex
     *
     * @return int 
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set logins
     *
     * @param int $logins
     * @return User
     */
    public function setLogins(\int $logins)
    {
        $this->logins = $logins;
        return $this;
    }

    /**
     * Get logins
     *
     * @return int 
     */
    public function getLogins()
    {
        return $this->logins;
    }

    /**
     * Set lastlogin
     *
     * @param int $lastlogin
     * @return User
     */
    public function setLastlogin(\int $lastlogin)
    {
        $this->lastlogin = $lastlogin;
        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return int 
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    /**
     * Get rsn
     *
     * @return integer 
     */
    public function getRsn()
    {
        return $this->rsn;
    }
}