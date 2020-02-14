<?php
namespace App\Controller;

use App\Controller\Helper\Session\Vars;
use App\Model\ViewContext;

/**
 * @see \Test\Controller\ViewControllerTest
 */
abstract class ViewController extends BaseController
{
    /** @var \App\Util\View */
    protected $view;

    public function __construct(ViewContext $viewContext)
    {
        parent::__construct($viewContext->baseContext);
        $this->view = $viewContext->view;
    }

    public function preDispatch(array $params)
    {
        if (parent::preDispatch($params)) {
            $sessionTimeout = ini_get('session.gc_maxlifetime');

            $passThruKeys = ['route'];

            $passThruVars = array_intersect_key($params, array_flip($passThruKeys));

            $this->view->addVars(
                $passThruVars +
                $this->config->getConfig() +
                [
                    'sessionTimeout' => $sessionTimeout
                ]
            );

            return true;
        }

        return false;
    }
}
