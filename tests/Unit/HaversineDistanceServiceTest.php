<?php

namespace Tests\Unit;

use App\Services\HaversineDistanceService;
use PHPUnit\Framework\TestCase;

class HaversineDistanceServiceTest extends TestCase
{
    private HaversineDistanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HaversineDistanceService();
    }

    public function test_identical_coordinates_return_zero(): void
    {
        $distance = $this->service->calculate(4.60971, -74.08175, 4.60971, -74.08175);
        $this->assertSame(0.0, $distance);
    }

    public function test_calculates_distance_accurately_between_known_points(): void
    {
        // Bogotá Plaza de Bolívar (4.5981, -74.0760) to El Dorado Airport (4.7016, -74.1469)
        // Distance ~ 13.9 - 14.2 km
        $distance = $this->service->calculate(4.5981, -74.0760, 4.7016, -74.1469);
        $this->assertGreaterThan(13.8, $distance);
        $this->assertLessThan(14.3, $distance);
    }

    public function test_result_is_rounded_to_four_decimal_places(): void
    {
        $distance = $this->service->calculate(4.6000, -74.0800, 4.6050, -74.0850);
        $decimals = strlen(substr(strrchr((string) $distance, '.'), 1));
        $this->assertLessThanOrEqual(4, $decimals);
    }
}
