<?php

namespace Tests\Unit\Services;

use App\Services\AttendanceService;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    public function test_distance_uses_haversine(): void
    {
        // Bandung (Gedung Sate) ke Jakarta (Monas) kurang lebih 118 km
        $km = (new AttendanceService)->distance(-6.9025, 107.6188, -6.1754, 106.8272) / 1000;

        $this->assertEqualsWithDelta(118, $km, 3);
    }

    public function test_within_radius_respects_flag_and_nearest_point(): void
    {
        $service = new AttendanceService;
        config([
            'attendance.radius_meter' => 100,
            'attendance.locations' => [['nama' => 'A', 'latitude' => -6.9, 'longitude' => 107.6]],
            'attendance.enforce_radius' => false,
        ]);
        $this->assertTrue($service->withinRadius(0, 0));

        config(['attendance.enforce_radius' => true]);
        $this->assertTrue($service->withinRadius(-6.9003, 107.6));
        $this->assertFalse($service->withinRadius(-6.91, 107.6));
    }
}
