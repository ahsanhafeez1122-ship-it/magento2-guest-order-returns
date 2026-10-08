<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ReturnRequest extends AbstractDb
{
    public const TABLE = 'ahsan_guest_return_request';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'entity_id');
    }
}
