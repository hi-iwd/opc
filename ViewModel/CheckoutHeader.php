<?php
/**
 * View model for the additive checkout chrome blocks (header, footnote,
 * secure badge).
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\ViewModel;

use IWD\OneStepCheckout\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CheckoutHeader implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly UrlInterface $url,
        private readonly CustomerUrl $customerUrl
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function isOnePage(): bool
    {
        return $this->config->isOnePage();
    }

    public function isMultiStep(): bool
    {
        return $this->config->isMultiStep();
    }

    /**
     * Auto-save only operates in one_page mode, so gate on the mode and the flag.
     */
    public function isAutoSaveShipping(): bool
    {
        return $this->config->isOnePage() && $this->config->isAutoSaveShipping();
    }

    public function getBackUrl(): string
    {
        return $this->url->getUrl('checkout/cart');
    }

    /**
     * Number of line items in the cart.
     */
    public function getItemsCount(): int
    {
        try {
            return (int)$this->checkoutSession->getQuote()->getItemsCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Logged-in customer's first name; '' for guests or when unavailable.
     */
    public function getCustomerFirstName(): string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return '';
        }

        try {
            return trim((string)$this->customerSession->getCustomerData()?->getFirstname());
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Login URL via the customer module (honours the after-login redirect config).
     */
    public function getSignInUrl(): string
    {
        return $this->customerUrl->getLoginUrl();
    }

    /**
     * URL of the configured "Terms of Service" CMS page, or '' when none is set.
     */
    public function getTermsUrl(): string
    {
        return $this->cmsPageUrl($this->config->getTermsPageIdentifier());
    }

    /**
     * URL of the configured "Privacy Policy" CMS page, or '' when none is set.
     */
    public function getPrivacyUrl(): string
    {
        return $this->cmsPageUrl($this->config->getPrivacyPageIdentifier());
    }

    private function cmsPageUrl(string $identifier): string
    {
        return $identifier === '' ? '' : $this->url->getUrl('', ['_direct' => $identifier]);
    }

    /**
     * Footnote legal-line template (with %1/%2 for the Terms/Privacy links).
     */
    public function getLegalTemplate(bool $autoSave): string
    {
        return $this->config->getContent($autoSave ? 'footnote_legal' : 'footnote_legal_plain');
    }

    /**
     * Compact (mobile) footnote legal-line template, same %1/%2 contract.
     */
    public function getLegalCompactTemplate(bool $autoSave): string
    {
        return $this->config->getContent($autoSave ? 'footnote_legal_compact' : 'footnote_legal_compact_plain');
    }

    public function getTrustSsl(): string
    {
        return $this->config->getContent('trust_ssl');
    }

    public function getTrustAutoSave(): string
    {
        return $this->config->getContent('trust_autosave');
    }

    public function getTrustSecure(): string
    {
        return $this->config->getContent('trust_secure');
    }

    public function getMsTrustSsl(): string
    {
        return $this->config->getContent('ms_trust_ssl');
    }

    public function getMsTrustReturns(): string
    {
        return $this->config->getContent('ms_trust_returns');
    }

    public function getMsTrustProtection(): string
    {
        return $this->config->getContent('ms_trust_protection');
    }

    public function isVirtual(): bool
    {
        return (bool) $this->checkoutSession->getQuote()->isVirtual();
    }
}
