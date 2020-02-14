<?php
namespace App\Controller;

use App\Controller\Helper\Session\Message;
use App\Controller\Helper\Session\ReturnUrl;
use App\Controller\Helper\Session\Vars;
use App\Model\BaseContext;
use App\Util\Config;
use App\Util\HeaderParams;
use App\Util\Logger;
use App\Util\Session;

/**
 * @see \Test\Controller\BaseControllerTest
 */
abstract class BaseController
{
    use ReturnUrl;
    use Message;

    /** @var Config */
    protected $config;

    /** @var Session */
    protected $session;

    /** @var HeaderParams */
    protected $headers;

    /** @var Logger */
    protected $logger;

    protected $isApi;
    
    public function __construct(BaseContext $baseContext)
    {
        $this->config = $baseContext->config;
        $this->session = $baseContext->session;
        $this->headers = $baseContext->headers;
        $this->logger = $baseContext->logger;
        $this->isApi = false;
    }

    /**
     * Returns true if the request should continue, false otherwise.
     *
     * @param array $params
     * @return boolean
     */
    public function preDispatch(array $params)
    {
        $this->session->start();
        
        $this->headers->set("Strict-Transport-Security: max-age=300; includeSubDomains; preload");

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
          $ip = '0.0.0.0';
        }

        $this->logger->setContext([
            'requestId' => $this->session->getRequestId(),
            'sessionId' => $this->session->getSessionId(),
            'sessionData' => $this->session->getBasic(),
            'ip' => $ip,
        ]);

        return true;
    }
}
