<?php
/**
 * Captures the order comment / newsletter opt-in from a logged-in customer's
 * payment save.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Plugin\Checkout;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class PaymentInformationManagementPlugin extends AbstractOrderFieldsPlugin
{
    /**
     * @param bool $result
     * @param int|string $cartId
     * @return bool
     */
    public function afterSavePaymentInformation(
        PaymentInformationManagementInterface $subject,
        $result,
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        $this->stash($paymentMethod);

        return $result;
    }
}
