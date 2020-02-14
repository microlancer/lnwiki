<?php
namespace App\Util;

/**
 * @codeCoverageIgnore
 */
class HeaderParams
{
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_JAVASCRIPT = 'application/javascript';
    const CONTENT_TYPE_CSS = 'text/css';
    const CONTENT_TYPE_EVENT_STREAM = 'text/event-stream';
    
    // use zlib.output_compression instead of this header
    const CONTENT_ENCODING_GZIP = 'gzip'; 
    
    const HEADER_IF_NONE_MATCH = 'If-None-Match';
    const HEADER_ETAG = 'ETag';
    
    const HTTP_OK = 200;
    const HTTP_NOT_AUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    
    private $contentType; 
    private $contentEncoding;
    private $apacheRequestHeaders;
    
    public function set($str)
    {
        header($str);
    }

    public function setResponseCode($code)
    {
        http_response_code($code);
    }
    
    public function setContentType($contentType)
    {
        header('Content-Type: ' . $contentType);
        $this->contentType = $contentType;
    }
    
    public function setContentEncoding($contentEncoding)
    {
        header('Content-Encoding: ' . $contentEncoding);
        $this->contentEncoding = $contentEncoding;
    }

    public function redirect($path)
    {
        $config = Di::getInstance()->get(Config::class);
        header('Location: ' . $config->get('baseUrl') . '/' . $path);
    }
    
    public function setCookie($name, $var, $expireDays = null)
    {
        if (!$expireDays) {
            setcookie($name, $var, null, '/');
        } else {
            setcookie($name, $var, time()+60*60*24*$expireDays, '/');
        }
    }
    
    public function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
    }
    
    public function getContentType()
    {
        return $this->contentType;
    }
    
    public function getRequestHeaders()
    {
        if (!$this->apacheRequestHeaders) {
            $this->apacheRequestHeaders = apache_request_headers();
        }
        
        return $this->apacheRequestHeaders;
    }
    
    public function getIfNoneMatch()
    {
        $headers = $this->getRequestHeaders();
        
        if (isset($headers['If-None-Match'])) {
            return $headers['If-None-Match'];
        }
        
        return false;
    }
    
    public function getLastEventId()
    {
        $headers = $this->getRequestHeaders();
        
        if (isset($headers['Last-Event-ID'])) {
            return $headers['Last-Event-ID'];
        }
        
        return false;
    }
    
    public function setETag($hash)
    {
        header('ETag: ' . $hash);
    }
    
    public function setCacheControl($values)
    {
        header('Cache-Control: ' . $values);
    }
}
