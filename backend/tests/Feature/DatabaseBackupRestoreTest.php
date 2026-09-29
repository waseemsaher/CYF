<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates a database backup and uploads it to s3-backups disk by default', function (): void {
    Storage::fake('s3-backups');

    AcademicYear::create([
        'name' => ['ar' => 'سنة تجريبية', 'en' => 'Test Year'],
        'sort_order' => 99,
    ]);

    $tempBackupDir = sys_get_temp_dir().'/cyf_test_backups_'.uniqid();
    mkdir($tempBackupDir, 0755, true);

    try {
        $exitCode = Artisan::call('db:backup', [
            '--path' => $tempBackupDir,
        ]);

        expect($exitCode)->toBe(0);

        // Check local file was created
        $localFiles = glob($tempBackupDir.'/cyf_db_*.sql.gz');
        expect($localFiles)->not->toBeEmpty();

        // Check s3-backups file was uploaded
        $s3Files = Storage::disk('s3-backups')->files('backups');
        expect($s3Files)->not->toBeEmpty();
        expect($s3Files[0])->toEndWith('.sql.gz');
    } finally {
        $files = glob($tempBackupDir.'/*');
        if (is_array($files)) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        @rmdir($tempBackupDir);
    }
});

it('restores database successfully from an s3-backups backup by default', function (): void {
    Storage::fake('s3-backups');

    // 1. Initial state: create record
    AcademicYear::create([
        'name' => ['ar' => 'سنة أصلية', 'en' => 'Original Year'],
        'sort_order' => 1,
    ]);

    expect(AcademicYear::where('sort_order', 1)->exists())->toBeTrue();

    $tempBackupDir = sys_get_temp_dir().'/cyf_test_restore_'.uniqid();
    mkdir($tempBackupDir, 0755, true);

    try {
        // 2. Perform backup to s3-backups default disk
        $backupExit = Artisan::call('db:backup', [
            '--path' => $tempBackupDir,
        ]);
        expect($backupExit)->toBe(0);

        $s3Files = Storage::disk('s3-backups')->files('backups');
        expect($s3Files)->not->toBeEmpty();

        // 3. Mutate database: create a second record
        AcademicYear::create([
            'name' => ['ar' => 'سنة مضافة بعد النسخ', 'en' => 'Post-Backup Year'],
            'sort_order' => 2,
        ]);
        expect(AcademicYear::where('sort_order', 2)->exists())->toBeTrue();

        // 4. Restore from s3-backups default disk
        $restoreExit = Artisan::call('db:restore', [
            '--from-s3' => true,
            '--force' => true,
        ]);

        expect($restoreExit)->toBe(0);

        // 5. Verify restored state
        expect(AcademicYear::where('sort_order', 1)->exists())->toBeTrue();
    } finally {
        $files = glob($tempBackupDir.'/*');
        if (is_array($files)) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        @rmdir($tempBackupDir);
    }
});

it('configures s3-backups disk with dedicated settings', function (): void {
    $config = config('filesystems.disks.s3-backups');

    expect($config)->toBeArray();
    expect($config['driver'])->toBe('s3');
    expect($config['visibility'])->toBe('private');
    expect(config('filesystems.disks.s3'))->not->toBeNull();
});
