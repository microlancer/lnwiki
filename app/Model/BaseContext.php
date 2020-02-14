<?php

namespace App\Model;

use App\Util\Config;
use App\Util\HeaderParams;
use App\Util\Logger;
use App\Util\Session;

class BaseContext
{
    public $config;
    public $session;
    public $headers;
    public $logger;

    public function __construct(Config $config, Session $session,
        HeaderParams $headers, 
        Logger $logger)
    {
        $this->config = $config;
        $this->session = $session;
        $this->headers = $headers;
        $this->logger = $logger;
    }
}
