<?php
/**
 * Adds the skin body classes to the checkout page server-side so the skin is
 * styled from the initial render. Renders nothing; gated on isEnabled().
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Block;

use IWD\OneStepCheckout\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class BodyClass extends Template
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        if ($this->config->isEnabled()) {
            $classes = ['iwd-osc', 'iwd-osc--' . str_replace('_', '-', $this->config->getLayoutMode())];

            if ($this->config->isOnePage() || $this->config->isMultiStep()) {
                $classes[] = 'iwd-osc-loading';
            }

            foreach ($classes as $class) {
                $this->pageConfig->addBodyClass($class);
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return '';
    }
}
