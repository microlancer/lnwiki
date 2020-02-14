<?php

namespace App\Util;

use App\Util\Session;

/**
 * Manages csrfTokens and hmacTokens. The CSRF token (or anti-CSRF) is meant
 * to be used with ANY form, and new ones (max 10) are generated each time
 * one is requested. Once the max is reached, the oldest token is invalid. A
 * new CSRF token is generated every time a form is POSTed. The CSRF system
 * exists even for non-logged-in users, as long as they have a session.
 * 
 * The HMAC token is meant to be used for a specific form type. The max is the
 * number of types of forms that can exist. The same token can be re-used as
 * long as the session is the same, and the form is the same.
 * 
 * The front-end does not need to request new CSRF tokens. The initial CSRF
 * token will be provided on the initial data payload. Subsequent tokens are
 * retrieved in the result of form POST actions. This token should be stored
 * globally for the application, and used for the next form post on ANY form.
 * 
 * The front-end should request an HMAC token through an initial GET of the
 * form. However, this HMAC token does not expire for the length of the 
 * session, so no further HMAC tokens for that form need to be retrieved. This
 * token should be used on all the form posts of that SPECIFIC form class.
 */
class CsrfTokens
{
    const CSRF_TOKENS = 'csrfTokens';
    const HMAC_KEYS = 'hmacKeys';
    const MAXIMUM_CSRF_TOKENS = 10;
    
    /** @var Session */
    private $session;
    private $logger;
    
    public function __construct(Session $session, Logger $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }
    
    public function createCsrfToken()
    {
        if (!is_null($this->session->get(self::CSRF_TOKENS))) {
            $csrfTokens = $this->session->get(self::CSRF_TOKENS);
        } else {
            $csrfTokens = [];
        }
        
        $csrfToken = bin2hex(random_bytes(64));
        
        $csrfTokens[] = $csrfToken;
        
        if (count($csrfTokens) > self::MAXIMUM_CSRF_TOKENS) {
            array_shift($csrfTokens);
        }
        
        $csrfTokens = array_values($csrfTokens);
        
        $this->session->set(self::CSRF_TOKENS, $csrfTokens);
        
        $this->logger->log('Setting tokens', Logger::DEBUG, ['tokens' => $csrfTokens, 'count' => count($csrfTokens)]);
        
        return $csrfToken;
    }
    
    public function createHmacKey($formClass)
    {
        if (!is_null($this->session->get(self::HMAC_KEYS))) {
            $hmacKeys = $this->session->get(self::HMAC_KEYS);
        } else {
            $hmacKeys = [];
        }
        
        if (!isset($hmacKeys[$formClass])) {
            $hmacKey = bin2hex(random_bytes(64));
            $hmacKeys[$formClass] = $hmacKey;
        } else {
            $hmacKey = $hmacKeys[$formClass];
        }
        
        $hmacToken = hash_hmac('sha256', $formClass, $hmacKey);
        $this->session->set(self::HMAC_KEYS, $hmacKeys);
        
        return $hmacToken;
    }
    
    public function createToken($formClass)
    {   
        return [ 
            'hmacToken' => $this->createCsrfToken(),
            'csrfToken' => $this->createHmacKey($formClass),
        ];
    }
    
    public function isValid($token, $formClass, $hmacToken)
    {
        $csrfTokens = $this->session->get(self::CSRF_TOKENS);
//        $this->logger->log('Checking tokens', Logger::DEBUG, ['token' => $token, 'tokens' => $csrfTokens]);        
        if (!is_array($csrfTokens)) {
            return false;
        }
        
        $csrfIsValid = false;
        
        // Do not simply check in_array here. We want to run hash_equals
        // to prevent timing attacks.
        foreach ($csrfTokens as $i => $storedToken) {
            $csrfIsValid = hash_equals($storedToken, $token);
            if ($csrfIsValid) {
                unset($csrfTokens[$i]);
                $this->session->set(self::CSRF_TOKENS, $csrfTokens);
                break;
            }
        }
        
        $hmacKeys = $this->session->get(self::HMAC_KEYS);
        $hmacKey = $hmacKeys[$formClass];
        $calculatedHmacToken = hash_hmac('sha256', $formClass, $hmacKey);
        $hmacIsValid = hash_equals($calculatedHmacToken, $hmacToken);
//        $this->logger->log('Checking tokens', Logger::DEBUG, ['hmacIsValid' => $hmacIsValid, 'csrfIsValid' => $csrfIsValid]);        
        return $hmacIsValid && $csrfIsValid;
    }
}