<?php
declare(strict_types=1);

namespace IWD\OneStepCheckout\Test\Unit\Plugin\Paypal;

use IWD\OneStepCheckout\Model\Config;
use IWD\OneStepCheckout\Plugin\Paypal\BuildNotationCodePlugin;
use Magento\Paypal\Model\AbstractConfig;
use PHPUnit\Framework\TestCase;

class BuildNotationCodePluginTest extends TestCase
{
    private $config;
    private $subject;
    private BuildNotationCodePlugin $plugin;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->subject = $this->createMock(AbstractConfig::class);
        $this->plugin = new BuildNotationCodePlugin($this->config);
    }

    public function testKeepsCoreCodeWhenModuleDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->config->expects($this->never())->method('getPaypalBnCode');

        $this->assertSame(
            'Magento_2_Community',
            $this->plugin->afterGetBuildNotationCode($this->subject, 'Magento_2_Community')
        );
    }

    public function testAppliesIwdDefaultCodeWhenEnabled(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getPaypalBnCode')->willReturn(Config::DEFAULT_PAYPAL_BN_CODE);

        $this->assertSame(
            'IWD_SP_PCP',
            $this->plugin->afterGetBuildNotationCode($this->subject, 'Magento_2_Community')
        );
    }

    public function testAppliesPartnerCodeWhenEnabledAndConfigured(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getPaypalBnCode')->willReturn('IWD_Example_BN');

        $this->assertSame(
            'IWD_Example_BN',
            $this->plugin->afterGetBuildNotationCode($this->subject, 'Magento_2_Community')
        );
    }
}
