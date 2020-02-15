<?php

namespace App\Model;

use App\Model\Factory\Invoice as InvoiceFactory;
use App\Util\Logger;

/**
 * Class InvoiceList
 *
 * @property int $userId
 * @property int $postId
 * @property int $offerId
 * @property int $tipId
 * @property int $boostId
 * @property array $statuses
 * @property array $invoices
 */
class InvoiceList
{
    use WithProperties;

    private $invoiceFactory;
    private $logger;

    public function __construct(InvoiceFactory $invoiceFactory, Logger $logger)
    {
        $this->defineProperties([
            'userId',
            'postId',
            'offerId',
            'tipId',
            'boostId',
            'statuses',
            'invoices',
        ]);

        $this->invoiceFactory = $invoiceFactory;
        $this->logger = $logger;
        $this->invoices = [];
    }

    public function addInvoice(Invoice $invoice)
    {
        $this->invoices[] = $invoice;
    }
}
