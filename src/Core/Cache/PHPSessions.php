<?php

namespace Core\Cache;

use Core\Event;

class PHPSessions implements CacheInterface
{
    private $tokenId;
    private $token;

    private $config;

    /**
     * {@inheritdoc}
     */
    public function __construct($pConfig)
    {
        $this->config = $pConfig;
        $this->startSession();

        //since we store don't want to have a second cookie beside our own from ClientAbstract,
        //we have to listen for token changes and the reset the session_id();
        Event::listen('core/client/token-changed', array($this, 'startSession'));
    }

    /**
     * {@inheritdoc}
     */
    public function testConfig($pConfig)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function startSession($pNewSession = null)
    {
        if ($this->config['ClientInstance']) {
            $this->tokenId = $this->config['ClientInstance']->getTokenId();
            $this->token = $this->config['ClientInstance']->getToken();
        } else {
            $this->tokenId = 'phpsession';
        }

        session_name($this->tokenId);

        if (!$this->token) {
            return false;
        }

        session_id($this->token);
        session_start();
    }

    /**
     * {@inheritdoc}
     */
    public function get($pKey)
    {
        return $_SESSION[$pKey];
    }

    /**
     * {@inheritdoc}
     */
    public function set($pKey, $pValue, $pTimeout = null)
    {
        return ($_SESSION[$pKey] = $pValue) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($pKey)
    {
        unset($_SESSION[$pKey]);
    }
}
