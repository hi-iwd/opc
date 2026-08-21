<?php
declare(strict_types=1);

namespace IWD\OneStepCheckout\Test\Unit\Plugin\Block;

use IWD\OneStepCheckout\Model\Config;
use IWD\OneStepCheckout\Plugin\Block\LayoutProcessorPlugin;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

class LayoutProcessorPluginTest extends TestCase
{
    /** @var Config&\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var CheckoutSession&\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutSession;

    /** @var HttpContext&\PHPUnit\Framework\MockObject\MockObject */
    private $httpContext;

    /** @var LayoutProcessor&\PHPUnit\Framework\MockObject\MockObject */
    private $subject;

    private LayoutProcessorPlugin $plugin;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->httpContext = $this->createMock(HttpContext::class);
        $this->subject = $this->createMock(LayoutProcessor::class);
        $this->plugin = new LayoutProcessorPlugin($this->config, $this->checkoutSession, $this->httpContext);
    }

    private function setVirtualQuote(bool $virtual): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('isVirtual')->willReturn($virtual);
        $this->checkoutSession->method('getQuote')->willReturn($quote);
    }

    private function setQuoteUnavailable(): void
    {
        $this->checkoutSession->method('getQuote')->willThrowException(new \RuntimeException('no quote'));
    }

    private function setLoggedIn(bool $loggedIn): void
    {
        $this->httpContext->method('getValue')
            ->with(CustomerContext::CONTEXT_AUTH)
            ->willReturn($loggedIn);
    }

    private function jsLayout(): array
    {
        return [
            'components' => [
                'checkout' => ['children' => [
                    'steps' => ['children' => [
                        'shipping-step' => ['children' => [
                            'shippingAddress' => ['children' => ['customer-email' => []]],
                        ]],
                        'billing-step' => ['children' => [
                            'payment' => ['children' => ['customer-email' => []]],
                        ]],
                    ]],
                    'sidebar' => ['children' => ['summary' => ['children' => []]]],
                ]],
            ],
        ];
    }

    private function shippingChildren(array $result): array
    {
        return $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children'];
    }

    private function paymentChildren(array $result): array
    {
        return $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children'];
    }

    private function checkoutChildren(array $result): array
    {
        return $result['components']['checkout']['children'];
    }

    private function stepsChildren(array $result): array
    {
        return $result['components']['checkout']['children']['steps']['children'];
    }

    private function summaryChildren(array $result): array
    {
        return $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];
    }

    public function testDisabledLeavesLayoutUntouchedAndTouchesNoCollaborators(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->checkoutSession->expects($this->never())->method('getQuote');
        $this->httpContext->expects($this->never())->method('getValue');

        $input = $this->jsLayout();

        $this->assertSame($input, $this->plugin->afterProcess($this->subject, $input));
    }

    public function testOnePageInjectsSectionsPlaceOrderAndFields(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(false);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $shipping = $this->shippingChildren($result);
        $payment = $this->paymentChildren($result);
        $summary = $result['components']['checkout']['children']['sidebar']['children']['summary']['children'];

        $this->assertArrayHasKey('iwd_section_contact', $shipping);
        $this->assertArrayHasKey('iwd_section_shipping', $shipping);
        $this->assertArrayHasKey('iwd_section_delivery', $shipping);
        $this->assertArrayHasKey('iwd_section_payment', $payment);
        $this->assertArrayHasKey('iwd_payment_locked', $payment);
        $this->assertArrayHasKey('iwd_order_comment', $payment);
        $this->assertArrayHasKey('iwd_newsletter', $payment);
        $this->assertArrayHasKey('iwd_place_order', $summary);
        // Guest account opt-in sits below the email input (sortOrder 110).
        $this->assertArrayHasKey('iwd_create_account', $shipping);
        $this->assertSame('customer-email', $shipping['iwd_create_account']['displayArea']);
        $this->assertSame(110, $shipping['iwd_create_account']['sortOrder']);
        // Guest numbering: 01 Contact ... 04 Payment.
        $this->assertSame('01', $shipping['iwd_section_contact']['number']);
        $this->assertSame('04', $payment['iwd_section_payment']['number']);
    }

    public function testOnePageLoggedInShowsIdentityCardAndRenumbers(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(true);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $shipping = $this->shippingChildren($result);
        $payment = $this->paymentChildren($result);

        // Logged-in: identity card (01), then Shipping (02), Delivery (03),
        // Payment (04); contact bits threaded by sortOrder in customer-email.
        $this->assertArrayHasKey('iwd_section_contact', $shipping);
        $this->assertSame('01', $shipping['iwd_section_contact']['number']);
        $this->assertSame('Signed in', $shipping['iwd_section_contact']['badge']);
        $this->assertSame('customer-email', $shipping['iwd_section_contact']['displayArea']);
        $this->assertArrayHasKey('iwd_customer_identity', $shipping);
        $this->assertSame('customer-email', $shipping['iwd_customer_identity']['displayArea']);
        $this->assertSame(110, $shipping['iwd_customer_identity']['sortOrder']);
        // No account opt-in for logged-in customers.
        $this->assertArrayNotHasKey('iwd_create_account', $shipping);
        $this->assertSame('02', $shipping['iwd_section_shipping']['number']);
        $this->assertSame('customer-email', $shipping['iwd_section_shipping']['displayArea']);
        $this->assertSame(120, $shipping['iwd_section_shipping']['sortOrder']);
        $this->assertSame('03', $shipping['iwd_section_delivery']['number']);
        $this->assertSame('04', $payment['iwd_section_payment']['number']);
    }

    public function testVirtualGuestCollapsesToContactAndPayment(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(true);
        $this->setLoggedIn(false);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $shipping = $this->shippingChildren($result);
        $payment = $this->paymentChildren($result);

        // Contact renders inside the payment step for virtual carts.
        $this->assertArrayNotHasKey('iwd_section_contact', $shipping);
        $this->assertArrayNotHasKey('iwd_section_shipping', $shipping);
        $this->assertArrayNotHasKey('iwd_section_delivery', $shipping);
        $this->assertArrayHasKey('iwd_section_contact', $payment);
        // Virtual guest: the account opt-in rides along in the payment step's
        // customer-email region.
        $this->assertArrayHasKey('iwd_create_account', $payment);
        $this->assertSame('customer-email', $payment['iwd_create_account']['displayArea']);
        $this->assertSame('01', $payment['iwd_section_contact']['number']);
        $this->assertSame('02', $payment['iwd_section_payment']['number']);
    }

    public function testVirtualLoggedInHasContactAndPaymentSections(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(true);
        $this->setLoggedIn(true);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $payment = $this->paymentChildren($result);

        // Logged-in virtual: Contact identity card (01) + Payment (02), both in
        // the payment step. No account opt-in (that is guest-only).
        $this->assertArrayHasKey('iwd_section_contact', $payment);
        $this->assertSame('01', $payment['iwd_section_contact']['number']);
        $this->assertTrue($payment['iwd_section_contact']['isFirst']);
        $this->assertArrayHasKey('iwd_customer_identity', $payment);
        $this->assertArrayNotHasKey('iwd_create_account', $payment);
        $this->assertSame('02', $payment['iwd_section_payment']['number']);
        $this->assertFalse($payment['iwd_section_payment']['isFirst']);
    }

    public function testUnreadableQuoteFallsBackToPhysicalLayout(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setQuoteUnavailable();
        $this->setLoggedIn(false);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $this->assertSame('01', $this->shippingChildren($result)['iwd_section_contact']['number']);
        $this->assertSame('04', $this->paymentChildren($result)['iwd_section_payment']['number']);
    }

    public function testMalformedLayoutIsReturnedUnchanged(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(false);

        foreach ([[], ['components' => []], ['components' => ['checkout' => []]]] as $input) {
            $this->assertSame($input, $this->plugin->afterProcess($this->subject, $input));
        }
    }

    public function testOnePageRelabelsSummaryTotals(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(false);

        $input = $this->jsLayout();
        $input['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals'] = [
            'children' => [
                'subtotal' => ['config' => ['title' => 'Cart Subtotal']],
                'grand-total' => ['config' => ['title' => 'Order Total']],
            ],
        ];

        $result = $this->plugin->afterProcess($this->subject, $input);
        $totals = $result['components']['checkout']['children']['sidebar']['children']['summary']['children']
            ['totals']['children'];

        $this->assertSame('Subtotal', $totals['subtotal']['config']['title']);
        // Note: with tax shown in the grand total, Magento_Tax's template uses
        // incl/excl labels instead of `title` - this override is best-effort.
        $this->assertSame('Total', $totals['grand-total']['config']['title']);
    }

    public function testOnePageLabelsBillingAddressForms(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(false);

        $input = $this->jsLayout();
        $input['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']['payments-list'] = [
                'children' => [
                    'checkmo-form' => ['children' => ['form-fields' => ['children' => [
                        'street' => ['children' => [0 => [], 1 => []]],
                        'company' => [],
                    ]]]],
                ],
            ];

        $result = $this->plugin->afterProcess($this->subject, $input);

        $fieldset = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']['payments-list']['children']
            ['checkmo-form']['children']['form-fields']['children'];

        $this->assertSame('Apt, suite, etc. · optional', $fieldset['street']['children'][1]['label']);
        $this->assertSame('Company · optional', $fieldset['company']['label']);
    }

    public function testOnePageLabelsStreetLine2AndCompany(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(false);

        $input = $this->jsLayout();
        $input['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset'] = [
                'children' => [
                    'street' => ['children' => [0 => [], 1 => []]],
                    'company' => [],
                ],
            ];

        $result = $this->plugin->afterProcess($this->subject, $input);

        $fieldset = $this->shippingChildren($result)['shipping-address-fieldset']['children'];

        $this->assertSame('Apt, suite, etc. · optional', $fieldset['street']['children'][1]['label']);
        $this->assertSame('Company · optional', $fieldset['company']['label']);
    }

    public function testUnknownModeSkipsStructureButKeepsOrderFields(): void
    {
        // Enabled but neither one_page nor multi_step: no structural injection,
        // but the order-fields still apply.
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(false);
        $this->config->method('isMultiStep')->willReturn(false);
        $this->setVirtualQuote(false);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $shipping = $this->shippingChildren($result);
        $payment = $this->paymentChildren($result);
        $summary = $this->summaryChildren($result);

        // No structural injections outside the skinned modes...
        $this->assertArrayNotHasKey('iwd_section_contact', $shipping);
        $this->assertArrayNotHasKey('iwd_payment_locked', $payment);
        $this->assertArrayNotHasKey('iwd_place_order', $summary);
        $this->assertArrayNotHasKey('iwd_multistep_progress', $this->checkoutChildren($result));
        // ...but the order-fields components still apply.
        $this->assertArrayHasKey('iwd_order_comment', $payment);
        $this->assertArrayHasKey('iwd_newsletter', $payment);
    }

    public function testMultiStepGuestInjectsProgressSummaryAndRelocatesFields(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(false);
        $this->config->method('isMultiStep')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(false);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $checkout = $this->checkoutChildren($result);
        $steps = $this->stepsChildren($result);
        $shipping = $this->shippingChildren($result);
        $payment = $this->paymentChildren($result);
        $summary = $this->summaryChildren($result);

        // 3-step chrome: progress bar, completed-step summary cards, Pay CTA.
        $this->assertArrayHasKey('iwd_multistep_progress', $checkout);
        $this->assertSame('progressBar', $checkout['iwd_multistep_progress']['displayArea']);
        $this->assertArrayHasKey('iwd_multistep_summary', $steps);
        $this->assertArrayHasKey('iwd_place_order', $summary);
        // No one_page numbered sections in multi_step.
        $this->assertArrayNotHasKey('iwd_section_contact', $shipping);
        $this->assertArrayNotHasKey('iwd_payment_locked', $payment);
        // Guest physical: newsletter relocates under the contact email in the
        // shipping (Information) step, NOT in payment.
        $this->assertArrayHasKey('iwd_newsletter', $shipping);
        $this->assertSame('customer-email', $shipping['iwd_newsletter']['displayArea']);
        $this->assertArrayNotHasKey('iwd_newsletter', $payment);
        // Free order-fields still apply.
        $this->assertArrayHasKey('iwd_order_comment', $payment);
    }

    public function testMultiStepVirtualGuestPutsNewsletterInPaymentStep(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(false);
        $this->config->method('isMultiStep')->willReturn(true);
        $this->setVirtualQuote(true);
        $this->setLoggedIn(false);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        // Virtual carts have no shipping step, so the newsletter opt-in lands in
        // the payment step's afterMethods region (not customer-email, which would
        // re-render the email field and prematurely flag it as required).
        $payment = $this->paymentChildren($result);
        $this->assertArrayHasKey('iwd_newsletter', $payment);
        $this->assertSame('afterMethods', $payment['iwd_newsletter']['displayArea']);
        $this->assertArrayNotHasKey('iwd_newsletter', $this->shippingChildren($result));
    }

    public function testMultiStepLoggedInAddsIdentityCard(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOnePage')->willReturn(false);
        $this->config->method('isMultiStep')->willReturn(true);
        $this->setVirtualQuote(false);
        $this->setLoggedIn(true);

        $result = $this->plugin->afterProcess($this->subject, $this->jsLayout());

        $shipping = $this->shippingChildren($result);
        $payment = $this->paymentChildren($result);

        // Logged-in physical: identity card in the shipping (Information) step's
        // customer-email region; newsletter stays in payment (no email there).
        $this->assertArrayHasKey('iwd_customer_identity', $shipping);
        $this->assertSame('customer-email', $shipping['iwd_customer_identity']['displayArea']);
        $this->assertArrayHasKey('iwd_newsletter', $payment);
        $this->assertSame('afterMethods', $payment['iwd_newsletter']['displayArea']);
    }
}
