<?php
/**
 * Applies the IWD PayPal partner attribution (BN) code while this checkout is
 * enabled. Core reads getBuildNotationCode() for both the BUTTONSOURCE
 * parameter on PayPal API calls and the data-partner-attribution-id attribute
 * on the Smart Buttons SDK, so this single after-plugin covers both. Core's
 * own code is kept whenever this checkout is disabled or inert (Hyvä).
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Plugin\Paypal;

use IWD\OneStepCheckout\Model\Config;
use Magento\Paypal\Model\AbstractConfig;

class BuildNotationCodePlugin
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @param string $result
     * @return string
     */
    public function afterGetBuildNotationCode(AbstractConfig $subject, $result)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        return $this->config->getPaypalBnCode();
    }
}
