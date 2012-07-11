<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Session
 */
class Session
{
    /**
     * @var integer $time
     */
    private $time;

    /**
     * @var string $ip
     */
    private $ip;

    /**
     * @var string $useragent
     */
    private $useragent;

    /**
     * @var string $language
     */
    private $language;

    /**
     * @var string $page
     */
    private $page;

    /**
     * @var integer $refreshed
     */
    private $refreshed;

    /**
     * @var text $extra
     */
    private $extra;

    /**
     * @var integer $created
     */
    private $created;

    /**
     * @var string $id
     */
    private $id;

    /**
     * @var User
     */
    private $user;


    /**
     * Set time
     *
     * @param integer $time
     * @return Session
     */
    public function setTime($time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * Get time
     *
     * @return integer 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return Session
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set useragent
     *
     * @param string $useragent
     * @return Session
     */
    public function setUseragent($useragent)
    {
        $this->useragent = $useragent;
        return $this;
    }

    /**
     * Get useragent
     *
     * @return string 
     */
    public function getUseragent()
    {
        return $this->useragent;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return Session
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Get language
     *
     * @return string 
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set page
     *
     * @param string $page
     * @return Session
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Get page
     *
     * @return string 
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set refreshed
     *
     * @param integer $refreshed
     * @return Session
     */
    public function setRefreshed($refreshed)
    {
        $this->refreshed = $refreshed;
        return $this;
    }

    /**
     * Get refreshed
     *
     * @return integer 
     */
    public function getRefreshed()
    {
        return $this->refreshed;
    }

    /**
     * Set extra
     *
     * @param text $extra
     * @return Session
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * Get extra
     *
     * @return text 
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Session
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
     * Set id
     *
     * @param string $id
     * @return Session
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Session
     */
    public function setUser(\User $user = null)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return User 
     */
    public function getUser()
    {
        return $this->user;
    }
}