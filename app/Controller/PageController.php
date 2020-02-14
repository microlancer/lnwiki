<?php

namespace App\Controller;

use App\Model\ViewContext;

class PageController extends ViewController
{
    public function __construct(ViewContext $viewContext)
    {
        parent::__construct($viewContext);
        
        $this->authNotRequired = [
            '',
            'index',
            'index/index',
        ];
    }
    
    public function indexAction(array $params)
    {
      $this->view->addVars(['name' => $params['name'], 'op' => $params['op']]);
      $this->view->render('index/index');
    }
}
