<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class PricingLogicTest extends TestCase
{
    public function testNightsCalculation()
    {
        $checkIn = new \DateTime('2023-12-01');
        $checkOut = new \DateTime('2023-12-05');
        
        $interval = $checkIn->diff($checkOut);
        $nights = $interval->days;

        $this->assertEquals(4, $nights, 'Nights calculation should correctly identify 4 nights.');
    }

    public function testTotalPriceCalculation()
    {
        $baseRate = 120.50;
        $nights = 4;
        
        $totalPrice = $baseRate * $nights;
        
        $this->assertEquals(482.00, $totalPrice, 'Total price calculation should be base rate multiplied by nights.');
    }
}
