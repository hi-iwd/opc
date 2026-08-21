<?php
/**
 * Reads the order-comment / newsletter extension attributes off the payment
 * method (sent by checkout JS) and stashes them in the checkout session for
 * the post-order observer.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Plugin\Checkout;

use IWD\OneStepCheckout\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\PaymentInterface;

abstract class AbstractOrderFieldsPlugin
{
    public const SESSION_COMMENT = 'iwd_osc_comment';
    public const SESSION_SUBSCRIBE = 'iwd_osc_subscribe';
    public const SESSION_CREATE_ACCOUNT = 'iwd_osc_create_account';

    private const COMMENT_MAX_LENGTH = 1000;

    public function __construct(
        protected readonly Config $config,
        protected readonly CheckoutSession $checkoutSession
    ) {
    }

    protected function stash(PaymentInterface $paymentMethod): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $extension = $paymentMethod->getExtensionAttributes();

        if (!$extension) {
            return;
        }

        // Only write when the attribute was actually sent, so a stashed value is
        // not wiped by an unrelated savePaymentInformation call (e.g. express wallets).
        if ($this->config->isOrderCommentEnabled() && $extension->getIwdOscComment() !== null) {
            $comment = mb_substr(trim((string)$extension->getIwdOscComment()), 0, self::COMMENT_MAX_LENGTH);
            $this->checkoutSession->setData(self::SESSION_COMMENT, $comment);
        }

        if ($this->config->isNewsletterEnabled() && $extension->getIwdOscSubscribe() !== null) {
            $this->checkoutSession->setData(self::SESSION_SUBSCRIBE, (bool)$extension->getIwdOscSubscribe());
        }

        if ($this->config->isGuestToCustomerEnabled() && $extension->getIwdOscCreateAccount() !== null) {
            $this->checkoutSession->setData(self::SESSION_CREATE_ACCOUNT, (bool)$extension->getIwdOscCreateAccount());
        }
    }
}
