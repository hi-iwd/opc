<?php
declare(strict_types=1);

namespace IWD\OneStepCheckout\Test\Unit\Model;

use IWD\OneStepCheckout\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private $scopeConfig;

    /** @var ModuleManager&MockObject */
    private $moduleManager;

    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->moduleManager = $this->createMock(ModuleManager::class);
        $this->config = new Config($this->scopeConfig, $this->moduleManager);
    }

    public function testIsEnabledIsFalseWhenHyvaCheckoutPresent(): void
    {
        $this->moduleManager->method('isEnabled')->with('Hyva_Checkout')->willReturn(true);
        // Must short-circuit before reading store config.
        $this->scopeConfig->expects($this->never())->method('isSetFlag');

        $this->assertFalse($this->config->isEnabled());
    }

    public function testIsEnabledReadsStoreFlagWhenNoHyva(): void
    {
        $this->moduleManager->method('isEnabled')->willReturn(false);
        $this->scopeConfig->method('isSetFlag')
            ->with(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testIsEnabledIsFalseWhenFlagOff(): void
    {
        $this->moduleManager->method('isEnabled')->willReturn(false);
        $this->scopeConfig->method('isSetFlag')->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    public function testGetRelocatableMethodsParsesAndSanitisesCsv(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_RELOCATABLE_METHODS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(' checkmo, free ,checkmo,bad code,<script>,cashondelivery, ');

        // Trimmed, de-duplicated, and non [a-zA-Z0-9_] codes dropped.
        $this->assertSame(
            ['checkmo', 'free', 'cashondelivery'],
            $this->config->getRelocatableMethods()
        );
    }

    public function testGetRelocatableMethodsEmptyWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame([], $this->config->getRelocatableMethods());
    }

    public function testGetLayoutModeReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_LAYOUT_MODE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('multi_step');

        $this->assertSame('multi_step', $this->config->getLayoutMode());
    }

    public function testGetLayoutModeFallsBackToOnePageForUnknownValue(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('nonsense');

        $this->assertSame(Config::LAYOUT_MODE_ONE_PAGE, $this->config->getLayoutMode());
    }

    public function testIsOnePageReflectsMode(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('one_page');
        $this->assertTrue($this->config->isOnePage());
    }

    public function testIsOnePageFalseForOtherModes(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('multi_step');
        $this->assertFalse($this->config->isOnePage());
    }

    public function testFeatureFlagsDelegateToScopeConfig(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturnMap([
            [Config::XML_PATH_AUTO_SAVE_SHIPPING, ScopeInterface::SCOPE_STORE, null, true],
            [Config::XML_PATH_ORDER_COMMENT, ScopeInterface::SCOPE_STORE, null, true],
            [Config::XML_PATH_NEWSLETTER, ScopeInterface::SCOPE_STORE, null, false],
            [Config::XML_PATH_GUEST_TO_CUSTOMER, ScopeInterface::SCOPE_STORE, null, true],
        ]);

        $this->assertTrue($this->config->isAutoSaveShipping());
        $this->assertTrue($this->config->isOrderCommentEnabled());
        $this->assertFalse($this->config->isNewsletterEnabled());
        $this->assertTrue($this->config->isGuestToCustomerEnabled());
    }

    public function testGetDesignTokensKeepsValidHexAndSanitisesFont(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['iwd_osc/design/primary_color', ScopeInterface::SCOPE_STORE, null, '#0F1115'],
            ['iwd_osc/design/accent_color', ScopeInterface::SCOPE_STORE, null, 'red; }malicious'],
            ['iwd_osc/design/border_color', ScopeInterface::SCOPE_STORE, null, ''],
            ['iwd_osc/design/text_color', ScopeInterface::SCOPE_STORE, null, '#abc'],
            ['iwd_osc/design/summary_bg', ScopeInterface::SCOPE_STORE, null, '#ffffff'],
            ['iwd_osc/design/font_family', ScopeInterface::SCOPE_STORE, null, 'Poppins, sans-serif'],
        ]);

        $tokens = $this->config->getDesignTokens();

        $this->assertSame('#0F1115', $tokens['--iwd-osc-primary']);
        $this->assertSame('#abc', $tokens['--iwd-osc-text']);
        $this->assertSame('#ffffff', $tokens['--iwd-osc-summary-bg']);
        $this->assertSame('Poppins, sans-serif', $tokens['--iwd-osc-font']);
        // Invalid colour (injection attempt) and empty value are dropped.
        $this->assertArrayNotHasKey('--iwd-osc-accent', $tokens);
        $this->assertArrayNotHasKey('--iwd-osc-border', $tokens);
    }

    public function testGetPaypalBnCodeValidatesFormat(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            [Config::XML_PATH_PAYPAL_BN_CODE, ScopeInterface::SCOPE_STORE, null, ' IWD_Example-BN1 '],
        ]);

        $this->assertSame('IWD_Example-BN1', $this->config->getPaypalBnCode());
    }

    public function testGetPaypalBnCodeFallsBackToIwdCodeOnInvalidAndEmpty(): void
    {
        $this->scopeConfig->method('getValue')->willReturnOnConsecutiveCalls('bad code!', null);

        // Literal on purpose: the attribution value itself is the contract.
        $this->assertSame('IWD_SP_PCP', $this->config->getPaypalBnCode());
        $this->assertSame('IWD_SP_PCP', $this->config->getPaypalBnCode());
    }

    public function testGetDesignTokensRejectsUnsafeFont(): void
    {
        // The font value flows into an inline <style>; anything outside the
        // allowlist (here: `;`, `{`, `}`) must be dropped entirely.
        $this->scopeConfig->method('getValue')->willReturnMap([
            [Config::XML_PATH_FONT_FAMILY, ScopeInterface::SCOPE_STORE, null, 'Poppins; } body { display:none'],
        ]);

        $this->assertArrayNotHasKey('--iwd-osc-font', $this->config->getDesignTokens());
    }

    public function testGetContentReturnsAdminOverrideWhenSet(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('iwd_osc/content/secure_badge', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('  Custom secure badge  ');

        // Admin value wins and is trimmed.
        $this->assertSame('Custom secure badge', $this->config->getContent('secure_badge'));
    }

    public function testGetContentFallsBackToTranslatableDefaultWhenBlank(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('iwd_osc/content/secure_badge', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('');

        // Blank admin value => the built-in default (via __()), so i18n works.
        $this->assertSame('Encrypted & secure checkout', $this->config->getContent('secure_badge'));
    }

    public function testGetContentReturnsEmptyForUnknownKey(): void
    {
        // Unknown keys short-circuit before touching store config.
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->assertSame('', $this->config->getContent('no_such_key'));
    }

    public function testGetContentMapCamelCasesKeys(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['iwd_osc/content/secure_badge', ScopeInterface::SCOPE_STORE, null, 'Badge'],
            ['iwd_osc/content/ms_subtitle_information', ScopeInterface::SCOPE_STORE, null, 'Sub'],
        ]);

        $this->assertSame(
            ['secureBadge' => 'Badge', 'msSubtitleInformation' => 'Sub'],
            $this->config->getContentMap(['secure_badge', 'ms_subtitle_information'])
        );
    }

    public function testCmsIdentifierReturnsValidUrlKey(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            [Config::XML_PATH_TERMS_PAGE, ScopeInterface::SCOPE_STORE, null, ' terms-and-conditions '],
            [Config::XML_PATH_PRIVACY_PAGE, ScopeInterface::SCOPE_STORE, null, 'privacy/policy_2'],
        ]);

        $this->assertSame('terms-and-conditions', $this->config->getTermsPageIdentifier());
        $this->assertSame('privacy/policy_2', $this->config->getPrivacyPageIdentifier());
    }

    public function testCmsIdentifierRejectsMalformedValue(): void
    {
        // A value with characters outside the CMS url_key set yields '' so the
        // footnote can never render a broken <a href>.
        $this->scopeConfig->method('getValue')->willReturnMap([
            [Config::XML_PATH_TERMS_PAGE, ScopeInterface::SCOPE_STORE, null, 'bad key! <script>'],
        ]);

        $this->assertSame('', $this->config->getTermsPageIdentifier());
    }
}
