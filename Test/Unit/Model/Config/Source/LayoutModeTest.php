<?php
declare(strict_types=1);

namespace IWD\OneStepCheckout\Test\Unit\Model\Config\Source;

use IWD\OneStepCheckout\Model\Config\Source\LayoutMode;
use PHPUnit\Framework\TestCase;

class LayoutModeTest extends TestCase
{
    public function testToOptionArrayExposesTwoModes(): void
    {
        $options = (new LayoutMode())->toOptionArray();

        $values = array_map(static fn ($o) => $o['value'], $options);

        $this->assertSame(
            [LayoutMode::ONE_PAGE, LayoutMode::MULTI_STEP],
            $values
        );

        foreach ($options as $option) {
            $this->assertArrayHasKey('label', $option);
            $this->assertInstanceOf(\Magento\Framework\Phrase::class, $option['label']);
            $this->assertNotSame('', (string)$option['label']);
        }
    }
}
