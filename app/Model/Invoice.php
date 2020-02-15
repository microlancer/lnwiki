<?php
namespace App\Model;

use App\Util\Config;
use App\Util\Date;

/**
 * Class Invoice
 * @property int $id
 * @property int $userId
 * @property int $postId
 * @property int $offerId
 * @property int $tipId
 * @property int $boostId
 * @property string $bolt11
 * @property string $label
 * @property int $status
 * @property int $createdTs
 * @property int $updatedTs
 */
class Invoice
{
    use WithProperties;
    
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_PAID = 3;
    const STATUS_REFUNDED = 4;
    
    // status message codes used by c-lightning
    const STATUS_UNPAID_MSG = 'unpaid';
    const STATUS_PAID_MSG = 'paid';
    const STATUS_EXPIRED_MSG = 'expired';

    private $config;
    private $date;
    
    public function __construct(Config $config, Date $date)
    {
        $this->defineProperties([
            'id',
            'userId',
            'postId',
            'offerId',
            'tipId',
            'boostId',
            'bolt11',
            'label',
            'status',
            'createdTs',
            'updatedTs',
        ]);

        $this->config = $config;
        $this->date = $date;
        $this->init(['postId' => 0, 'offerId' => 0, 'tipId' => 0, 'boostId' => 0]);
    }
    
    public function getCreatedTimeElapsed()
    {
        return $this->date->timeElapsed($this->createdTs);
    }
    
    public function getLabelAsHtml()
    {
        $label = preg_replace("/\n/", "<br>", htmlentities(trim($this->label)));
        return $label;
    }
}
