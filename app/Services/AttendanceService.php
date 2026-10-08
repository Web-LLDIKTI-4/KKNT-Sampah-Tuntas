<?php

namespace App\Services;

use App\Models\Kehadiran;

class AttendanceService
{
    public const IZIN_STATUSES = ['izin', 'sakit', 'cuti', 'kuliah'];

    public const BLOCKING_STATUSES = ['izin', 'sakit', 'cuti', 'kuliah', 'libur nasional'];

    /**
     * Jarak dua titik dalam meter (rumus haversine).
     */
    public function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Jarak ke titik lokasi terdekat dari config/attendance.php, null bila tidak ada titik.
     */
    public function nearestDistance(float $lat, float $lng): ?float
    {
        $distances = array_map(
            fn (array $loc) => $this->distance($lat, $lng, (float) $loc['latitude'], (float) $loc['longitude']),
            config('attendance.locations', [])
        );

        return $distances ? min($distances) : null;
    }

    public function withinRadius(float $lat, float $lng): bool
    {
        if (! config('attendance.enforce_radius')) {
            return true;
        }
        $nearest = $this->nearestDistance($lat, $lng);

        return $nearest !== null && $nearest <= (float) config('attendance.radius_meter');
    }

    /**
     * Alasan penolakan absensi datang/pulang hari ini, null bila boleh.
     */
    public function rejectReason(?Kehadiran $today, string $mode): ?string
    {
        if ($today && in_array($today->status_kehadiran, self::BLOCKING_STATUSES, true)) {
            return 'Anda sudah mengajukan '.$today->status_kehadiran.' hari ini.';
        }
        if ($mode === 'datang' && $today?->waktu_masuk) {
            return 'Anda sudah melakukan absen datang hari ini.';
        }
        if ($mode === 'pulang' && ! $today?->waktu_masuk) {
            return 'Silakan absen datang terlebih dahulu.';
        }
        if ($mode === 'pulang' && $today?->waktu_pulang) {
            return 'Anda sudah melakukan absen pulang hari ini.';
        }

        return null;
    }
}
