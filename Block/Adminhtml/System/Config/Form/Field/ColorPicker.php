<?php

declare(strict_types=1);

namespace IWD\OneStepCheckout\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ColorPicker extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = $element->getElementHtml();
        $value = $this->_escaper->escapeHtml($element->getData('value'));
        $html .= '<script type="text/javascript">
            require(["jquery", "jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    $("#' . $element->getHtmlId() . '").ColorPicker({
                        color: "' . ($value ? $value : '000000') . '",
                        onChange: function (hsb, hex, rgb) {
                            $("#' . $element->getHtmlId() . '").val("#" + hex);
                        }
                    });
                });
            });
        </script>';
        return $html;
    }
}
