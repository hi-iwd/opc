<?php
/**
 * IWD One Page Checkout (free tier).
 *
 * Enhances Magento 2's native Knockout checkout via chain-safe RequireJS mixins.
 * Zero class preferences, zero route overrides, zero KO template replacements.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'IWD_OneStepCheckout', __DIR__);
