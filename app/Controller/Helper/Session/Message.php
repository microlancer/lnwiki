<?php

namespace App\Controller\Helper\Session;

trait Message
{
    protected function setMessage($message)
    {
        $this->session->set(Vars::MESSAGE, $message);
    }

    protected function loadMessageIntoView()
    {
        $this->view->addVars([
            Vars::MESSAGE => $this->session->getOnce(Vars::MESSAGE)
        ]);
    }

    protected function setErrorMessage($errorMessage)
    {
        $this->session->set(Vars::ERROR_MESSAGE, $errorMessage);
    }

    protected function loadErrorMessageIntoView()
    {
        $this->view->addVars([
            Vars::ERROR_MESSAGE => $this->session->getOnce(Vars::ERROR_MESSAGE)
        ]);
    }
}
