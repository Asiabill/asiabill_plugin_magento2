<?php

namespace Asiabill\Payment\Model\Option;

use \Magento\Framework\Data\OptionSourceInterface;

class ElementsStyle implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Inner (Single line display)')],
            ['value' => '1', 'label' =>__('block (Two line display)')]
        ];
    }
}