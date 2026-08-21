<?php
/**
 * Publishes the module's admin config to window.checkoutConfig.iwdOsc.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\UrlInterface;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly CustomerUrl $customerUrl,
        private readonly UrlInterface $url
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfig(): array
    {
        if (!$this->config->isEnabled()) {
            return ['iwdOsc' => ['enabled' => false]];
        }

        return [
            'iwdOsc' => [
                'enabled' => true,
                'layoutMode' => $this->config->getLayoutMode(),
                'autoSaveShipping' => $this->config->isAutoSaveShipping(),
                'relocatableMethods' => $this->config->getRelocatableMethods(),
                'orderComment' => $this->config->isOrderCommentEnabled(),
                'newsletter' => $this->config->isNewsletterEnabled(),
                'guestToCustomer' => $this->config->isGuestToCustomerEnabled(),
                'signOutUrl' => $this->customerUrl->getLogoutUrl(),
                'cartUrl' => $this->url->getUrl('checkout/cart'),
                'content' => $this->config->getContentMap([
                    'secure_badge',
                    'newsletter_label',
                    'order_notes_label',
                    'create_account_label',
                    'locked_title',
                    'locked_body',
                    'locked_body_plain',
                    'locked_link_shipping',
                    'locked_link_delivery',
                    'locked_step_contact',
                    'locked_step_shipping',
                    'locked_step_delivery',
                    'locked_status',
                    'ms_subtitle_information',
                    'ms_subtitle_shipping',
                    'ms_subtitle_payment',
                ]),
            ],
        ];
    }
}
