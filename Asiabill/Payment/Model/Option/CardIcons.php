<?php

namespace Asiabill\Payment\Model\Option;

use \Magento\Framework\Data\OptionSourceInterface;

class CardIcons implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'visa_card', 'label' =>__('Visa')],
            ['value' => 'master_card', 'label' =>__('MasterCard')],
            ['value' => 'jcb_card', 'label' =>__('JCB')],
            ['value' => 'ae_card', 'label' =>__('American Express')],
            ['value' => 'diners_card', 'label' =>__('Diners')],
        ];
    }
}