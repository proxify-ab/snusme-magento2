<?php
/**
 * Copyright © 2015 Infomodus. All rights reserved.
 */

namespace Infomodus\Upslabel\Model;

use Magento\Framework\Model\AbstractModel;

class Address extends AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Infomodus\Upslabel\Model\ResourceModel\Address');
    }
}