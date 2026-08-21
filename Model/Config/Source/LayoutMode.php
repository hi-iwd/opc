<?php
/**
 * Admin options for the checkout layout mode.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Model\Config\Source;

use IWD\OneStepCheckout\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class LayoutMode implements OptionSourceInterface
{
    public const ONE_PAGE = Config::LAYOUT_MODE_ONE_PAGE;
    public const MULTI_STEP = Config::LAYOUT_MODE_MULTI_STEP;

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ONE_PAGE, 'label' => __('One Page (all sections visible, auto-save)')],
            ['value' => self::MULTI_STEP, 'label' => __('Multi-Step (native steps, restyled)')]
        ];
    }
}
