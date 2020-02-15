<?php

namespace App\Model\Factory;

use App\Model\Page as PageModel;
use App\Util\Di;

class Page
{
    private $di;

    public function __construct(Di $di)
    {
        $this->di = $di;
    }

    /**
    *
    * @return PageModel
    */
    public function createPage()
    {
        return $this->di->create(PageModel::class);
    }
}
