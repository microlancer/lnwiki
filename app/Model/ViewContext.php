<?php

namespace App\Model;

use App\Model\BaseContext;
use App\Util\View;

class ViewContext
{
    public $baseContext;
    public $view;

    public function __construct(BaseContext $baseContext, View $view)
    {
        $this->baseContext = $baseContext;
        $this->view = $view;
    }
}
