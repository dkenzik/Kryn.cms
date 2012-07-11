<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Session
 */
class Session
{
    /**
     * @var string $session_id
     */
    private $session_id;

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
     * @var integer $id
     */
    private $id;

    /**
     * @var User
     */
    private $user_rsn;


    /**
     * Set session_id
     *
     * @param string $sessionId
     * @return Session
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;
        return $this;
    }

    /**
     * Get session_id
     *
     * @return string 
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user_rsn
     *
     * @param User $userRsn
     * @return Session
     */
    public function setUserRsn(\User $userRsn = null)
    {
        $this->user_rsn = $userRsn;
        return $this;
    }

    /**
     * Get user_rsn
     *
     * @return User 
     */
    public function getUserRsn()
    {
        return $this->user_rsn;
    }
}