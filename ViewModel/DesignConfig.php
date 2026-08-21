<?php
/**
 * Exposes the validated admin design tokens as CSS custom properties.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\ViewModel;

use IWD\OneStepCheckout\Model\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class DesignConfig implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @return array<string, string> CSS custom property => validated value
     */
    public function getTokens(): array
    {
        return $this->config->getDesignTokens();
    }
}
