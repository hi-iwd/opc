<?php
/**
 * Typed configuration reader for IWD One-Step Checkout.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'iwd_osc/general/enabled';

    public const XML_PATH_LAYOUT_MODE = 'iwd_osc/general/layout_mode';

    public const XML_PATH_AUTO_SAVE_SHIPPING = 'iwd_osc/general/auto_save_shipping';

    public const XML_PATH_RELOCATABLE_METHODS = 'iwd_osc/general/relocatable_methods';

    public const XML_PATH_ORDER_COMMENT = 'iwd_osc/features/order_comment';

    public const XML_PATH_NEWSLETTER = 'iwd_osc/features/newsletter';

    public const XML_PATH_GUEST_TO_CUSTOMER = 'iwd_osc/features/guest_to_customer';

    public const XML_PATH_FONT_FAMILY = 'iwd_osc/design/font_family';

    public const XML_PATH_PAYPAL_BN_CODE = 'iwd_osc/partners/paypal_bn_code';

    /** IWD's PayPal partner attribution code, used when no valid override is set. */
    public const DEFAULT_PAYPAL_BN_CODE = 'IWD_SP_PCP';

    public const XML_PATH_TERMS_PAGE = 'iwd_osc/legal/terms_cms_page';

    public const XML_PATH_PRIVACY_PAGE = 'iwd_osc/legal/privacy_cms_page';

    /** Admin design field => CSS custom property the skin reads. */
    private const DESIGN_TOKENS = [
        'iwd_osc/design/primary_color' => '--iwd-osc-primary',
        'iwd_osc/design/accent_color' => '--iwd-osc-accent',
        'iwd_osc/design/border_color' => '--iwd-osc-border',
        'iwd_osc/design/text_color' => '--iwd-osc-text',
        'iwd_osc/design/summary_bg' => '--iwd-osc-summary-bg',
    ];

    private const CSS_VAR_FONT = '--iwd-osc-font';

    /** Admin-editable content: field key (iwd_osc/content/<key>) => built-in default. */
    private const CONTENT_DEFAULTS = [
        'contact_hint_guest'           => 'Order confirmation only · no spam',
        'contact_hint_customer'        => 'Order updates go to your account email',
        'create_account_label'         => 'You can create an account after checkout',
        'newsletter_label'             => 'Email me news and exclusive offers',
        'order_notes_label'            => 'Order notes (optional)',
        'secure_badge'                 => 'Encrypted & secure checkout',
        'trust_ssl'                    => 'SSL encrypted checkout',
        'trust_autosave'               => 'Your details auto-save as you type',
        'trust_secure'                 => 'Safe & secure payment',
        'footnote_legal'               => 'By placing your order, you agree to our %1 and acknowledge our %2. All shipping and payment details are saved automatically as you complete each section · no Next button.',
        'footnote_legal_plain'         => 'By placing your order, you agree to our %1 and acknowledge our %2.',
        'footnote_legal_compact'       => 'Auto-saved as you typed. By placing your order you agree to our %1 and %2.',
        'footnote_legal_compact_plain' => 'By placing your order you agree to our %1 and %2.',
        'locked_title'                 => 'Payment options will appear here',
        'locked_body'                  => 'Complete your %1 and pick a %2 above. Payment options load automatically once your shipping is saved · no Next button.',
        'locked_body_plain'            => 'Complete your %1 and pick a %2 above. Payment options appear once your shipping is saved.',
        'locked_link_shipping'         => 'shipping address',
        'locked_link_delivery'         => 'delivery method',
        'locked_step_contact'          => 'Contact',
        'locked_step_shipping'         => 'Shipping address',
        'locked_step_delivery'         => 'Delivery method',
        'locked_status'                => 'in progress',
        'ms_subtitle_information'      => "Tell us where to send your order. We'll send your receipt to the email you provide.",
        'ms_subtitle_shipping'         => "Choose how you'd like your order delivered.",
        'ms_subtitle_payment'          => 'All transactions are secure and encrypted. We never store your full card number.',
        'ms_trust_ssl'                 => '256-bit SSL encrypted',
        'ms_trust_returns'             => 'Free returns within 30 days',
        'ms_trust_protection'          => 'Buyer protection guarantee',
    ];

    /** When present, Hyvä Checkout replaces native checkout and this module stays inert. */
    private const HYVA_CHECKOUT_MODULE = 'Hyva_Checkout';

    public const LAYOUT_MODE_ONE_PAGE = 'one_page';
    public const LAYOUT_MODE_MULTI_STEP = 'multi_step';

    private const LAYOUT_MODES = [
        self::LAYOUT_MODE_ONE_PAGE,
        self::LAYOUT_MODE_MULTI_STEP
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ModuleManager $moduleManager
    ) {
    }

    /**
     * True only when the admin toggle is on AND Hyvä Checkout is not enabled.
     */
    public function isEnabled(?int $storeId = null): bool
    {
        if ($this->moduleManager->isEnabled(self::HYVA_CHECKOUT_MODULE)) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Layout mode (one_page | multi_step); unknown values fall back to one_page.
     */
    public function getLayoutMode(?int $storeId = null): string
    {
        $mode = (string)$this->scopeConfig->getValue(
            self::XML_PATH_LAYOUT_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return in_array($mode, self::LAYOUT_MODES, true) ? $mode : self::LAYOUT_MODE_ONE_PAGE;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isOnePage(?int $storeId = null): bool
    {
        return $this->getLayoutMode($storeId) === self::LAYOUT_MODE_ONE_PAGE;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isMultiStep(?int $storeId = null): bool
    {
        return $this->getLayoutMode($storeId) === self::LAYOUT_MODE_MULTI_STEP;
    }

    /**
     * Auto-save shipping on section complete ("no Next button").
     */
    public function isAutoSaveShipping(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_AUTO_SAVE_SHIPPING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Payment method codes whose Place Order action may move to the summary CTA.
     *
     * @return string[]
     */
    public function getRelocatableMethods(?int $storeId = null): array
    {
        $raw = (string)$this->scopeConfig->getValue(
            self::XML_PATH_RELOCATABLE_METHODS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $methods = [];

        foreach (explode(',', $raw) as $code) {
            $code = trim($code);

            if ($code !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $code) === 1) {
                $methods[] = $code;
            }
        }

        return array_values(array_unique($methods));
    }

    /**
     * Opt-in features (default off).
     */
    public function isOrderCommentEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::XML_PATH_ORDER_COMMENT, $storeId);
    }

    public function isNewsletterEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::XML_PATH_NEWSLETTER, $storeId);
    }

    public function isGuestToCustomerEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::XML_PATH_GUEST_TO_CUSTOMER, $storeId);
    }

    private function flag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * PayPal partner attribution (BN) code; falls back to the IWD default when
     * the configured value is empty or malformed.
     */
    public function getPaypalBnCode(?int $storeId = null): string
    {
        $code = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_PAYPAL_BN_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return preg_match('/^[A-Za-z0-9_\-]+$/', $code) === 1 ? $code : self::DEFAULT_PAYPAL_BN_CODE;
    }

    /**
     * CMS page URL key linked as "Terms of Service"; '' when unset or malformed.
     */
    public function getTermsPageIdentifier(?int $storeId = null): string
    {
        return $this->cmsIdentifier(self::XML_PATH_TERMS_PAGE, $storeId);
    }

    /**
     * CMS page URL key linked as "Privacy Policy"; '' when unset or malformed.
     */
    public function getPrivacyPageIdentifier(?int $storeId = null): string
    {
        return $this->cmsIdentifier(self::XML_PATH_PRIVACY_PAGE, $storeId);
    }

    /**
     * Read and validate a CMS page URL key; invalid values yield ''.
     */
    private function cmsIdentifier(string $path, ?int $storeId): string
    {
        $identifier = trim((string)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return preg_match('#^[A-Za-z0-9/_.-]+$#', $identifier) === 1 ? $identifier : '';
    }

    /**
     * Resolve an editable content string: admin value when set, else the
     * translatable default. Unknown keys yield ''.
     */
    public function getContent(string $key, ?int $storeId = null): string
    {
        if (!array_key_exists($key, self::CONTENT_DEFAULTS)) {
            return '';
        }

        $value = trim((string)$this->scopeConfig->getValue(
            'iwd_osc/content/' . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : (string)__(self::CONTENT_DEFAULTS[$key]);
    }

    /**
     * Resolved content strings for the given keys, camelCased for the JS blob.
     *
     * @param string[] $keys
     * @return array<string, string>
     */
    public function getContentMap(array $keys, ?int $storeId = null): array
    {
        $out = [];

        foreach ($keys as $key) {
            $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $out[$camel] = $this->getContent($key, $storeId);
        }

        return $out;
    }

    /**
     * Admin design overrides as CSS custom property => value. Only validated
     * values are returned, making the inline <style> block safe to print.
     *
     * @return array<string, string>
     */
    public function getDesignTokens(?int $storeId = null): array
    {
        $tokens = [];

        foreach (self::DESIGN_TOKENS as $path => $cssVar) {
            $value = trim((string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));

            if ($value !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1) {
                $tokens[$cssVar] = $value;
            }
        }

        $font = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_FONT_FAMILY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($font !== '' && preg_match('/^[a-zA-Z0-9 ,_"\'\-]+$/', $font) === 1) {
            $tokens[self::CSS_VAR_FONT] = $font;
        }

        return $tokens;
    }
}
