<?php

namespace Asiabill\Payment\Model\Option;

use \Magento\Framework\Data\OptionSourceInterface;

class CheckoutModel implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Asiabill Elements (recommended for most websites)')],
            ['value' => '1', 'label' =>__('Asiabill Checkout (redirected for payment)')]
        ];
    }
}