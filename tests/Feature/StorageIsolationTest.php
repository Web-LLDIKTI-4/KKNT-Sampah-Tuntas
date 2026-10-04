<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use Database\Seeders\PanduanSeeder;

class StorageIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_local_disk_is_faked_by_default(): void
    {
        foreach (self::FAKED_DISKS as $disk) {
            $diskRoot = realpath(Storage::disk($disk)->path('')) ?: Storage::disk($disk)->path('');

            $this->assertStringStartsWith(storage_path('framework/testing/disks'), $diskRoot, "Disk [$disk] harus fake di test");
        }
    }

    public function test_database_seeder_does_not_touch_real_storage(): void
    {
        $realStorageRoots = [storage_path('app/private'), storage_path('app/public')];
        $snapshotBefore = $this->snapshotFiles($realStorageRoots);

        $this->seed(PanduanSeeder::class);

        $this->assertNotEmpty(Storage::disk('local')->files('panduan/dummy'), 'Seeder seharusnya menulis ke disk fake');
        $this->assertSame($snapshotBefore, $this->snapshotFiles($realStorageRoots));
    }

    // Path => ukuran & mtime; perubahan apa pun di storage nyata membuat snapshot berbeda
    private function snapshotFiles(array $directories): array
    {
        $snapshot = [];
        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }
            foreach (File::allFiles($directory) as $file) {
                $snapshot[$file->getPathname()] = $file->getSize().'@'.$file->getMTime();
            }
        }
        ksort($snapshot);

        return $snapshot;
    }
}
