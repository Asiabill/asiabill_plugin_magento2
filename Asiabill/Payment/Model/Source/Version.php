<?php
/**
 * Created by PhpStorm.
 * User: tenny
 * Date: 2021/8/20
 * Time: 15:07
 */

namespace Asiabill\Payment\Model\Source;


class Version extends \Magento\Config\Block\System\Config\Form\Field
{

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return \Asiabill\Payment\Model\PaymentMethod::$version;
    }

}