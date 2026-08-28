<?php
/**
 * Injects the numbered section headers, payment-locked placeholder and relocated
 * Place Order CTA into the native checkout jsLayout. Additive only; null-guards
 * every hop so it tolerates other LayoutProcessor plugins.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Plugin\Block;

use IWD\OneStepCheckout\Model\Config;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;

class LayoutProcessorPlugin
{
    private const SECTION_COMPONENT = 'IWD_OneStepCheckout/js/view/section';

    private const PLACE_ORDER_COMPONENT = 'IWD_OneStepCheckout/js/view/summary-place-order';

    private const PAYMENT_LOCKED_COMPONENT = 'IWD_OneStepCheckout/js/view/payment-locked';

    private const ORDER_COMMENT_COMPONENT = 'IWD_OneStepCheckout/js/view/order-comment';

    private const NEWSLETTER_COMPONENT = 'IWD_OneStepCheckout/js/view/newsletter-subscribe';

    private const CREATE_ACCOUNT_COMPONENT = 'IWD_OneStepCheckout/js/view/create-account';

    private const CUSTOMER_IDENTITY_COMPONENT = 'IWD_OneStepCheckout/js/view/customer-identity';

    private const MULTISTEP_PROGRESS_COMPONENT = 'IWD_OneStepCheckout/js/view/multistep-progress';

    private const MULTISTEP_SUMMARY_COMPONENT = 'IWD_OneStepCheckout/js/view/multistep-summary';

    public function __construct(
        private readonly Config $config,
        private readonly CheckoutSession $checkoutSession,
        private readonly HttpContext $httpContext
    ) {
    }

    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        if (!$this->config->isEnabled()) {
            return $jsLayout;
        }

        $payment = &$this->childrenRef(
            $jsLayout,
            ['checkout', 'steps', 'billing-step', 'payment']
        );

        if ($this->config->isOnePage()) {
            $this->injectOnePageStructure($jsLayout, $payment);
        } elseif ($this->config->isMultiStep()) {
            $this->injectMultiStepStructure($jsLayout);
        }

        // Order-fields (self-gated in their JS components) apply in every mode.
        if (is_array($payment)) {
            $payment['iwd_order_comment'] = [
                'component' => self::ORDER_COMMENT_COMPONENT,
                'displayArea' => 'afterMethods',
                'sortOrder' => 10,
            ];
            // multi_step relocates the newsletter opt-in to the Information step.
            if (!$this->config->isMultiStep()) {
                $payment['iwd_newsletter'] = [
                    'component' => self::NEWSLETTER_COMPONENT,
                    'displayArea' => 'afterMethods',
                    'sortOrder' => 20,
                ];
            }
        }

        return $jsLayout;
    }

    /**
     * @param array<string, mixed> $jsLayout
     * @param array<string, mixed>|null $payment reference into $jsLayout
     */
    private function injectOnePageStructure(array &$jsLayout, &$payment): void
    {
        // Auth from HttpContext (the FPC vary key), not the session.
        $isGuest = !$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);

        if ($this->isVirtualQuote()) {
            // Virtual cart: no shipping step, email renders in the payment step.
            if (is_array($payment)) {
                if ($isGuest) {
                    if (isset($payment['customer-email']) && is_array($payment['customer-email'])) {
                        $payment['customer-email']['sortOrder'] = 100;
                    }
                    $payment['iwd_section_contact'] = $this->sectionNode(
                        'customer-email',
                        '01',
                        (string)__('Contact'),
                        $this->config->getContent('contact_hint_guest'),
                        true
                    );
                    $payment['iwd_create_account'] = $this->createAccountNode();
                } else {
                    // Logged-in: a Contact identity card (01) replaces the guest
                    // email field, rendered in the payment step's customer-email region.
                    $payment['iwd_section_contact'] = $this->sectionNode(
                        'customer-email',
                        '01',
                        (string)__('Contact'),
                        $this->config->getContent('contact_hint_customer'),
                        true,
                        '',
                        '',
                        (string)__('Signed in'),
                        'iwd-osc-section-header__badge--signedin'
                    );
                    $payment['iwd_customer_identity'] = [
                        'component' => self::CUSTOMER_IDENTITY_COMPONENT,
                        'displayArea' => 'customer-email',
                        'sortOrder' => 110,
                    ];
                }

                // Contact is 01 in both auth states, so Payment is always 02.
                $payment['iwd_section_payment'] = $this->sectionNode(
                    'beforeMethods',
                    '02',
                    (string)__('Payment'),
                    (string)__('Locked'),
                    false,
                    'iwd-osc-section-header__hint--locked'
                );
            }
        } else {
            // Physical (or mixed) cart.
            $shipping = &$this->childrenRef(
                $jsLayout,
                ['checkout', 'steps', 'shipping-step', 'shippingAddress']
            );

            if (is_array($shipping)) {
                if ($isGuest) {
                    if (isset($shipping['customer-email']) && is_array($shipping['customer-email'])) {
                        $shipping['customer-email']['sortOrder'] = 100;
                    }
                    $shipping['iwd_section_contact'] = $this->sectionNode(
                        'customer-email',
                        '01',
                        (string)__('Contact'),
                        $this->config->getContent('contact_hint_guest'),
                        true
                    );
                    $shipping['iwd_section_shipping'] = $this->sectionNode(
                        'before-form',
                        '02',
                        (string)__('Shipping Address')
                    );
                    $shipping['iwd_create_account'] = $this->createAccountNode();
                } else {
                    // Logged-in: a Contact identity card (01) replaces the guest
                    // email field, then Shipping Address (02), both in the
                    // customer-email region, threaded by sortOrder (0 -> 110 -> 120).
                    $shipping['iwd_section_contact'] = $this->sectionNode(
                        'customer-email',
                        '01',
                        (string)__('Contact'),
                        $this->config->getContent('contact_hint_customer'),
                        true,
                        '',
                        '',
                        (string)__('Signed in'),
                        'iwd-osc-section-header__badge--signedin'
                    );
                    $shipping['iwd_customer_identity'] = [
                        'component' => self::CUSTOMER_IDENTITY_COMPONENT,
                        'displayArea' => 'customer-email',
                        'sortOrder' => 110,
                    ];
                    $shipping['iwd_section_shipping'] = $this->sectionNode(
                        'customer-email',
                        '02',
                        (string)__('Shipping Address'),
                        '',
                        false,
                        '',
                        'saved-addresses',
                        '',
                        '',
                        120
                    );
                }
                $autoSaveDelivery = $this->config->isAutoSaveShipping();
                $shipping['iwd_section_delivery'] = $this->sectionNode(
                    'before-shipping-method-form',
                    '03',
                    (string)__('Delivery Method'),
                    $autoSaveDelivery ? (string)__('Auto-saved') : '',
                    false,
                    '',
                    // 'delivery-zip' lets section.js append a live "· zip NNNNN" suffix.
                    $autoSaveDelivery ? 'delivery-zip' : ''
                );

                $this->labelAddressFields($shipping);
            }

            if (is_array($payment)) {
                $payment['iwd_section_payment'] = $this->sectionNode(
                    'beforeMethods',
                    '04',
                    (string)__('Payment'),
                    (string)__('Locked'),
                    false,
                    'iwd-osc-section-header__hint--locked'
                );
            }
        }

        $this->labelBillingAddressFields($jsLayout);

        if (is_array($payment)) {
            $payment['iwd_payment_locked'] = [
                'component' => self::PAYMENT_LOCKED_COMPONENT,
                'displayArea' => 'beforeMethods',
                'sortOrder' => 10,
            ];
            // Second Place Order instance: hidden on desktop, the sticky bottom
            // CTA on mobile where the sidebar collapses into a modal.
            $payment['iwd_place_order_sticky'] = [
                'component' => self::PLACE_ORDER_COMPONENT,
                'displayArea' => 'afterMethods',
                'sortOrder' => 30,
            ];
        }

        $summary = &$this->childrenRef($jsLayout, ['checkout', 'sidebar', 'summary']);

        if (is_array($summary)) {
            $summary['iwd_place_order'] = [
                'component' => self::PLACE_ORDER_COMPONENT,
                'sortOrder' => 1000,
            ];
        }

        // "Subtotal" / "Total" instead of the native "Cart Subtotal" / "Order Total".
        $totals = &$this->childrenRef($jsLayout, ['checkout', 'sidebar', 'summary', 'totals']);

        if (is_array($totals)) {
            if (isset($totals['subtotal']) && is_array($totals['subtotal'])) {
                $totals['subtotal']['config']['title'] = (string)__('Subtotal');
            }

            if (isset($totals['grand-total']) && is_array($totals['grand-total'])) {
                $totals['grand-total']['config']['title'] = (string)__('Total');
            }
        }
    }

    /**
     * True when the active quote is fully virtual; false if the quote can't be read.
     */
    private function isVirtualQuote(): bool
    {
        try {
            return (bool)$this->checkoutSession->getQuote()->isVirtual();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Build a section-header jsLayout node for a given render region.
     */
    private function sectionNode(
        string $displayArea,
        string $number,
        string $title,
        string $hint = '',
        bool $isFirst = false,
        string $hintClass = '',
        string $hintType = '',
        string $badge = '',
        string $badgeClass = '',
        int $sortOrder = 0
    ): array {
        return [
            'component' => self::SECTION_COMPONENT,
            'displayArea' => $displayArea,
            'sortOrder' => $sortOrder,
            'number' => $number,
            'title' => $title,
            'hint' => $hint,
            'hintClass' => $hintClass,
            'isFirst' => $isFirst,
            'hintType' => $hintType,
            'badge' => $badge,
            'badgeClass' => $badgeClass,
        ];
    }

    /**
     * The guest "create an account after checkout" opt-in checkbox, rendered
     * below the email input. Self-gates on the feature flag and guest state.
     */
    private function createAccountNode(): array
    {
        return [
            'component' => self::CREATE_ACCOUNT_COMPONENT,
            'displayArea' => 'customer-email',
            'sortOrder' => 110,
        ];
    }

    /**
     * Relabel street line 2 and Company on the shipping address fieldset.
     *
     * @param array<string, mixed> $shipping children of shippingAddress
     */
    private function labelAddressFields(array &$shipping): void
    {
        if (!isset($shipping['shipping-address-fieldset']['children'])
            || !is_array($shipping['shipping-address-fieldset']['children'])
        ) {
            return;
        }

        $this->relabelAddressFieldset($shipping['shipping-address-fieldset']['children']);
    }

    /**
     * Same relabelling for every per-payment-method billing address form.
     *
     * @param array<string, mixed> $jsLayout
     */
    private function labelBillingAddressFields(array &$jsLayout): void
    {
        $paymentsList = &$this->childrenRef(
            $jsLayout,
            ['checkout', 'steps', 'billing-step', 'payment', 'payments-list']
        );

        if (!is_array($paymentsList)) {
            return;
        }

        foreach ($paymentsList as &$formNode) {
            if (isset($formNode['children']['form-fields']['children'])
                && is_array($formNode['children']['form-fields']['children'])
            ) {
                $this->relabelAddressFieldset($formNode['children']['form-fields']['children']);
            }
        }
        unset($formNode);
    }

    /**
     * @param array<string, mixed> $fieldset children of an address fieldset
     */
    private function relabelAddressFieldset(array &$fieldset): void
    {
        if (isset($fieldset['street']['children'][1]) && is_array($fieldset['street']['children'][1])) {
            $fieldset['street']['children'][1]['label'] = (string)__('Apt, suite, etc. · optional');
        }

        if (isset($fieldset['company']) && is_array($fieldset['company'])) {
            $fieldset['company']['label'] = (string)__('Company · optional');
        }
    }

    /**
     * Multi-step (3-step) structure: add the custom progress bar; the step
     * split and Continue controls are driven by the mixins + CSS.
     *
     * @param array<string, mixed> $jsLayout
     */
    private function injectMultiStepStructure(array &$jsLayout): void
    {
        $checkout = &$this->childrenRef($jsLayout, ['checkout']);

        if (is_array($checkout)) {
            $checkout['iwd_multistep_progress'] = [
                'component' => self::MULTISTEP_PROGRESS_COMPONENT,
                'displayArea' => 'progressBar',
                'sortOrder' => 0,
            ];
        }

        $isVirtual = $this->isVirtualQuote();
        $shippingAddress = &$this->childrenRef(
            $jsLayout,
            ['checkout', 'steps', 'shipping-step', 'shippingAddress']
        );
        $payment = &$this->childrenRef($jsLayout, ['checkout', 'steps', 'billing-step', 'payment']);

        if ($isVirtual) {
            $contactRegion = &$payment;
        } else {
            $contactRegion = &$shippingAddress;
        }

        if (!$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            if ($isVirtual) {
                if (is_array($payment)) {
                    $payment['iwd_create_account'] = [
                        'component' => self::CREATE_ACCOUNT_COMPONENT,
                        'displayArea' => 'afterMethods',
                        'sortOrder' => 15,
                    ];
                }
            } elseif (is_array($contactRegion)) {
                $contactRegion['iwd_create_account'] = $this->createAccountNode();
            }
        } elseif (is_array($contactRegion)) {
            // Logged-in: a Contact identity card replaces the email field.
            $contactRegion['iwd_customer_identity'] = [
                'component' => self::CUSTOMER_IDENTITY_COMPONENT,
                'displayArea' => 'customer-email',
                'sortOrder' => 110,
            ];
        }

        if (is_array($payment)) {
            $payment['iwd_newsletter'] = [
                'component' => self::NEWSLETTER_COMPONENT,
                'displayArea' => 'afterMethods',
                'sortOrder' => 20,
            ];
        }

        // Completed-step summary cards at the top of the main column.
        $steps = &$this->childrenRef($jsLayout, ['checkout', 'steps']);

        if (is_array($steps)) {
            $steps['iwd_multistep_summary'] = [
                'component' => self::MULTISTEP_SUMMARY_COMPONENT,
                'sortOrder' => 0,
            ];
        }

        if (is_array($payment)) {
            $payment['iwd_place_order_sticky'] = [
                'component' => self::PLACE_ORDER_COMPONENT,
                'displayArea' => 'afterMethods',
                'sortOrder' => 30,
            ];
        }

        // "Pay" CTA in the order summary; reuses the one_page relocated Place Order.
        $summary = &$this->childrenRef($jsLayout, ['checkout', 'sidebar', 'summary']);

        if (is_array($summary)) {
            $summary['iwd_place_order'] = [
                'component' => self::PLACE_ORDER_COMPONENT,
                'sortOrder' => 1000,
            ];
        }

        // "Subtotal" / "Total" instead of native "Cart Subtotal" / "Order Total".
        $totals = &$this->childrenRef($jsLayout, ['checkout', 'sidebar', 'summary', 'totals']);

        if (is_array($totals)) {
            if (isset($totals['subtotal']) && is_array($totals['subtotal'])) {
                $totals['subtotal']['config']['title'] = (string)__('Subtotal');
            }

            if (isset($totals['grand-total']) && is_array($totals['grand-total'])) {
                $totals['grand-total']['config']['title'] = (string)__('Total');
            }
        }
    }

    /**
     * Reference to a nested node's `children` array, or a null reference when
     * any hop is missing.
     *
     * @param array<string, mixed> $jsLayout
     * @param string[] $path
     * @return array<string, mixed>|null
     */
    private function &childrenRef(array &$jsLayout, array $path)
    {
        $null = null;

        if (!isset($jsLayout['components']) || !is_array($jsLayout['components'])) {
            return $null;
        }

        $node = &$jsLayout['components'];

        foreach ($path as $name) {
            if (!isset($node[$name]['children']) || !is_array($node[$name]['children'])) {
                return $null;
            }
            $node = &$node[$name]['children'];
        }

        return $node;
    }
}
