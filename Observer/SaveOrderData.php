<?php
/**
 * Post-order handling for the order comment, newsletter opt-in and guest
 * account creation. Every step is config-gated and exception-guarded.
 */
declare(strict_types=1);

namespace IWD\OneStepCheckout\Observer;

use IWD\OneStepCheckout\Model\Config;
use IWD\OneStepCheckout\Plugin\Checkout\AbstractOrderFieldsPlugin;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class SaveOrderData implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderCustomerManagementInterface $orderCustomerService,
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(EventObserver $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $order = $observer->getEvent()->getData('order');

        if (!$order instanceof Order || !$order->getId()) {
            return;
        }

        // Read-once: values are consumed and cleared before any gate so they
        // can never leak into a later order in the same session.
        $comment = (string)$this->checkoutSession->getData(AbstractOrderFieldsPlugin::SESSION_COMMENT, true);
        $subscribe = (bool)$this->checkoutSession->getData(AbstractOrderFieldsPlugin::SESSION_SUBSCRIBE, true);
        $createAccount = (bool)$this->checkoutSession->getData(AbstractOrderFieldsPlugin::SESSION_CREATE_ACCOUNT, true);

        $this->checkoutSession->setData(AbstractOrderFieldsPlugin::SESSION_SUBSCRIBE, false);
        $this->checkoutSession->setData(AbstractOrderFieldsPlugin::SESSION_COMMENT, '');
        $this->checkoutSession->setData(AbstractOrderFieldsPlugin::SESSION_CREATE_ACCOUNT, false);

        $this->saveComment($order, $comment);
        $this->createAccountForGuest($order, $createAccount);
        $this->subscribe($order, $subscribe);
    }

    private function saveComment(Order $order, string $comment): void
    {
        if (!$this->config->isOrderCommentEnabled() || $comment === '') {
            return;
        }

        try {
            $order->addCommentToStatusHistory($comment, false, true);
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            $this->logger->error('[IWD_OSC] save comment failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function subscribe(Order $order, bool $subscribe): void
    {
        if (!$this->config->isNewsletterEnabled() || !$subscribe || !$order->getCustomerEmail()) {
            return;
        }

        try {
            $customerId = (int)$order->getCustomerId();
            $storeId = (int)$order->getStoreId();

            if ($customerId) {
                $this->subscriptionManager->subscribeCustomer($customerId, $storeId);
            } else {
                $this->subscriptionManager->subscribe((string)$order->getCustomerEmail(), $storeId);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[IWD_OSC] newsletter subscribe failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function createAccountForGuest(Order $order, bool $optedIn): void
    {
        if (!$optedIn
            || !$this->config->isGuestToCustomerEnabled()
            || !$order->getCustomerIsGuest()
            || $this->customerSession->isLoggedIn()
        ) {
            return;
        }

        try {
            $this->orderCustomerService->create((int)$order->getId());
        } catch (\Throwable $e) {
            $this->logger->error('[IWD_OSC] guest account creation failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
