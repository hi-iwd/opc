<?php
/**
 * Captures the order comment / newsletter opt-in from a guest's payment save
 * (the guest interface adds the $email argument).
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Plugin\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class GuestPaymentInformationManagementPlugin extends AbstractOrderFieldsPlugin
{
    /**
     * @param bool $result
     * @param int|string $cartId
     * @param string $email
     * @return bool
     */
    public function afterSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $result,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        $this->stash($paymentMethod);

        return $result;
    }
}
