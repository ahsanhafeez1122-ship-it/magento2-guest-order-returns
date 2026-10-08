<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnRequest;

use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use AhsanHafeez\GuestReturns\Model\ReturnRequest;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(ReturnRequest::class, ReturnRequestResource::class);
    }
}
