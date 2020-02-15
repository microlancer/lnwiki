<?php

namespace App\Model\Factory;

use App\Model\Invoice as InvoiceModel;
use App\Model\InvoiceList;
use App\Util\Di;

class Invoice
{
    private $di;

    public function __construct(Di $di)
    {
        $this->di = $di;
    }

    /**
    *
    * @return InvoiceModel
    */
    public function createInvoice()
    {
        return $this->di->create(InvoiceModel::class);
    }
    
    /**
     * @return InvoiceList
     */
    public function createInvoiceList()
    {
        return $this->di->create(InvoiceList::class);
    }
}
