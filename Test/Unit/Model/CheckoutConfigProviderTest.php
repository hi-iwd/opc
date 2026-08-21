<?php
declare(strict_types=1);

namespace IWD\OneStepCheckout\Test\Unit\Model;

use IWD\OneStepCheckout\Model\CheckoutConfigProvider;
use IWD\OneStepCheckout\Model\Config;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class CheckoutConfigProviderTest extends TestCase
{
    public function testGetConfigEmitsIwdOscPayload(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('getLayoutMode')->willReturn('one_page');
        $config->method('isAutoSaveShipping')->willReturn(true);
        $config->method('getRelocatableMethods')->willReturn(['checkmo', 'free']);
        $config->method('isOrderCommentEnabled')->willReturn(false);
        $config->method('isNewsletterEnabled')->willReturn(true);
        $config->method('isGuestToCustomerEnabled')->willReturn(true);
        $config->method('getContentMap')->willReturn(['secureBadge' => 'Secure', 'lockedTitle' => 'Locked']);

        $customerUrl = $this->createMock(CustomerUrl::class);
        $customerUrl->method('getLogoutUrl')->willReturn('https://store.test/customer/account/logout/');

        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->with('checkout/cart')->willReturn('https://store.test/checkout/cart/');

        $result = (new CheckoutConfigProvider($config, $customerUrl, $url))->getConfig();

        $this->assertSame(
            [
                'iwdOsc' => [
                    'enabled' => true,
                    'layoutMode' => 'one_page',
                    'autoSaveShipping' => true,
                    'relocatableMethods' => ['checkmo', 'free'],
                    'orderComment' => false,
                    'newsletter' => true,
                    'guestToCustomer' => true,
                    'signOutUrl' => 'https://store.test/customer/account/logout/',
                    'cartUrl' => 'https://store.test/checkout/cart/',
                    'content' => ['secureBadge' => 'Secure', 'lockedTitle' => 'Locked'],
                ],
            ],
            $result
        );
    }

    public function testGetConfigEmitsMinimalPayloadWhenDisabled(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(false);
        $config->expects($this->never())->method('getRelocatableMethods');

        $customerUrl = $this->createMock(CustomerUrl::class);
        $customerUrl->expects($this->never())->method('getLogoutUrl');

        $url = $this->createMock(UrlInterface::class);
        $url->expects($this->never())->method('getUrl');

        $this->assertSame(
            ['iwdOsc' => ['enabled' => false]],
            (new CheckoutConfigProvider($config, $customerUrl, $url))->getConfig()
        );
    }
}
