<?php
declare(strict_types=1);

namespace IWD\OneStepCheckout\Test\Unit\Observer;

use IWD\OneStepCheckout\Model\Config;
use IWD\OneStepCheckout\Observer\SaveOrderData;
use IWD\OneStepCheckout\Plugin\Checkout\AbstractOrderFieldsPlugin;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class SaveOrderDataTest extends TestCase
{
    private $config;
    private $checkoutSession;
    private $customerSession;
    private $orderRepository;
    private $orderCustomerService;
    private $subscriptionManager;
    private SaveOrderData $observer;

    /** @var string[] */
    private array $clearedKeys = [];

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->customerSession = $this->createMock(CustomerSession::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderCustomerService = $this->createMock(OrderCustomerManagementInterface::class);
        $this->subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);

        $this->clearedKeys = [];

        $this->observer = new SaveOrderData(
            $this->config,
            $this->checkoutSession,
            $this->customerSession,
            $this->orderRepository,
            $this->orderCustomerService,
            $this->subscriptionManager,
            $this->createMock(LoggerInterface::class)
        );
    }

    private function eventWith(?Order $order): Observer
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('order')->willReturn($order);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    private function stashSession(string $comment, bool $subscribe, bool $createAccount = false): void
    {
        $this->checkoutSession->method('getData')->willReturnCallback(
            function ($key, $clear = false) use ($comment, $subscribe, $createAccount) {
                if ($clear) {
                    $this->clearedKeys[] = $key;
                }

                return [
                    AbstractOrderFieldsPlugin::SESSION_COMMENT => $comment,
                    AbstractOrderFieldsPlugin::SESSION_SUBSCRIBE => $subscribe,
                    AbstractOrderFieldsPlugin::SESSION_CREATE_ACCOUNT => $createAccount,
                ][$key] ?? null;
            }
        );
    }

    private function assertSessionCleared(): void
    {
        $this->assertSame(
            [
                AbstractOrderFieldsPlugin::SESSION_COMMENT,
                AbstractOrderFieldsPlugin::SESSION_SUBSCRIBE,
                AbstractOrderFieldsPlugin::SESSION_CREATE_ACCOUNT,
            ],
            $this->clearedKeys
        );
    }

    public function testDisabledModuleIsFullyTransparent(): void
    {
        // When the module is off it must be a complete no-op - it does not even
        // read or clear the checkout session.
        $this->config->method('isEnabled')->willReturn(false);
        $this->stashSession('Stale comment', true);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('11');
        $order->expects($this->never())->method('addCommentToStatusHistory');

        $this->orderRepository->expects($this->never())->method('save');
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');
        $this->orderCustomerService->expects($this->never())->method('create');

        $this->observer->execute($this->eventWith($order));

        $this->assertSame([], $this->clearedKeys);
    }

    public function testDoesNothingWhenOrderMissing(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->stashSession('Comment', true);

        $this->orderRepository->expects($this->never())->method('save');
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->orderCustomerService->expects($this->never())->method('create');

        $this->observer->execute($this->eventWith(null));

        $this->assertSame([], $this->clearedKeys);
    }

    public function testSavesCommentSubscribesAndCreatesGuestAccount(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOrderCommentEnabled')->willReturn(true);
        $this->config->method('isNewsletterEnabled')->willReturn(true);
        $this->config->method('isGuestToCustomerEnabled')->willReturn(true);

        $this->stashSession('Leave at front desk', true, true);
        $this->customerSession->method('isLoggedIn')->willReturn(false);

        // Raw getters return DB values (numeric strings); the observer casts them.
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('11');
        $order->method('getCustomerId')->willReturn(null);
        $order->method('getCustomerEmail')->willReturn('guest@example.com');
        $order->method('getStoreId')->willReturn('1');
        $order->method('getCustomerIsGuest')->willReturn(true);

        $order->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with('Leave at front desk', false, true);
        $this->orderRepository->expects($this->once())->method('save')->with($order);
        $this->subscriptionManager->expects($this->once())
            ->method('subscribe')->with($this->identicalTo('guest@example.com'), $this->identicalTo(1));
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');
        $this->orderCustomerService->expects($this->once())
            ->method('create')->with($this->identicalTo(11));

        $this->observer->execute($this->eventWith($order));

        $this->assertSessionCleared();
    }

    public function testDoesNotCreateGuestAccountWhenOptedOut(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOrderCommentEnabled')->willReturn(false);
        $this->config->method('isNewsletterEnabled')->willReturn(false);
        $this->config->method('isGuestToCustomerEnabled')->willReturn(true);

        // Feature on, but the guest left the opt-in unchecked.
        $this->stashSession('', false, false);
        $this->customerSession->method('isLoggedIn')->willReturn(false);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('14');
        $order->method('getCustomerIsGuest')->willReturn(true);

        $this->orderCustomerService->expects($this->never())->method('create');

        $this->observer->execute($this->eventWith($order));

        $this->assertSessionCleared();
    }

    public function testCustomerOrderSubscribesViaCustomerId(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isNewsletterEnabled')->willReturn(true);

        $this->stashSession('', true);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('12');
        $order->method('getCustomerId')->willReturn('7');
        $order->method('getCustomerEmail')->willReturn('customer@example.com');
        $order->method('getStoreId')->willReturn('1');
        $order->method('getCustomerIsGuest')->willReturn(false);

        $this->subscriptionManager->expects($this->once())
            ->method('subscribeCustomer')->with($this->identicalTo(7), $this->identicalTo(1));
        $this->subscriptionManager->expects($this->never())->method('subscribe');

        $this->observer->execute($this->eventWith($order));
    }

    public function testDiscardsStashWhenFeaturesDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isOrderCommentEnabled')->willReturn(false);
        $this->config->method('isNewsletterEnabled')->willReturn(false);
        $this->config->method('isGuestToCustomerEnabled')->willReturn(false);

        $this->stashSession('Should be discarded', true);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('13');
        $order->expects($this->never())->method('addCommentToStatusHistory');

        $this->orderRepository->expects($this->never())->method('save');
        $this->subscriptionManager->expects($this->never())->method('subscribe');
        $this->subscriptionManager->expects($this->never())->method('subscribeCustomer');
        $this->orderCustomerService->expects($this->never())->method('create');

        $this->observer->execute($this->eventWith($order));

        $this->assertSessionCleared();
    }
}
