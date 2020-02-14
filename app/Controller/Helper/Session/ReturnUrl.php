<?php

namespace App\Controller\Helper\Session;

trait ReturnUrl
{
    protected function setReturnUrl($route)
    {
        $this->session->set(Vars::RETURN_URL, $route);
    }

    protected function getReturnUrl()
    {
        return $this->session->getOnce(Vars::RETURN_URL);
    }
}
