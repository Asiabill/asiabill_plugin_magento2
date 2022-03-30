<?php

namespace Asiabill\Payment\Model\Option;

use \Magento\Framework\Data\OptionSourceInterface;

class Model implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Test')],
            ['value' => '1', 'label' =>__('Line')]
        ];
    }
}