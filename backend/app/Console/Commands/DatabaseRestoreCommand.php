<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseRestoreCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'db:restore
                            {file? : The backup filename or local file path}
                            {--from-s3 : Download backup from S3 storage}
                            {--disk=s3 : The storage disk to pull the backup from}
                            {--force : Force the operation without confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Restore a database backup from local storage or S3 bucket';

    public function handle(): int
    {
        $fileArg = $this->argument('file');
        $fromS3 = (bool) $this->option('from-s3');
        $diskName = (string) $this->option('disk');
        $localDir = storage_path('backups');

        $backupPath = null;
        $tempFileToDelete = null;

        if ($fromS3 || (is_string($fileArg) && (str_starts_with($fileArg, 's3://') || str_starts_with($fileArg, 'backups/')))) {
            $s3Key = $fileArg ?: $this->getLatestS3BackupKey($diskName);
            if (! $s3Key) {
                $this->error("No backups found in S3 disk '{$diskName}' under 'backups/'.");

                return self::FAILURE;
            }

            if (str_starts_with($s3Key, 's3://')) {
                $s3Key = preg_replace('#^s3://[^/]+/#', '', $s3Key);
            }
            if (! str_starts_with($s3Key, 'backups/')) {
                $s3Key = "backups/{$s3Key}";
            }

            $this->info("Fetching backup '{$s3Key}' from storage disk '{$diskName}'...");
            if (! Storage::disk($diskName)->exists($s3Key)) {
                $this->error("Backup file '{$s3Key}' does not exist on disk '{$diskName}'.");

                return self::FAILURE;
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'cyf_restore_');
            if ($tmpFile === false) {
                $this->error('Failed to create temporary file for download.');

                return self::FAILURE;
            }

            $content = Storage::disk($diskName)->get($s3Key);
            file_put_contents($tmpFile, $content);
            $backupPath = $tmpFile;
            $tempFileToDelete = $tmpFile;
        } else {
            if ($fileArg) {
                if (file_exists($fileArg)) {
                    $backupPath = $fileArg;
                } elseif (file_exists($localDir.'/'.$fileArg)) {
                    $backupPath = $localDir.'/'.$fileArg;
                } else {
                    // Try S3 if not found locally
                    if (Storage::disk($diskName)->exists("backups/{$fileArg}")) {
                        $this->info("File not found locally, found on S3 disk '{$diskName}'. Downloading...");

                        return $this->call('db:restore', [
                            'file' => $fileArg,
                            '--from-s3' => true,
                            '--disk' => $diskName,
                            '--force' => $this->option('force'),
                        ]);
                    }

                    $this->error("Backup file '{$fileArg}' not found locally or on S3.");

                    return self::FAILURE;
                }
            } else {
                // Find latest local backup
                $files = glob($localDir.'/cyf_db_*.sql.gz');
                if (empty($files)) {
                    $this->warn('No local backups found. Checking S3...');

                    return $this->call('db:restore', [
                        '--from-s3' => true,
                        '--disk' => $diskName,
                        '--force' => $this->option('force'),
                    ]);
                }
                rsort($files);
                $backupPath = $files[0];
            }
        }

        if (! file_exists($backupPath) || filesize($backupPath) === 0) {
            $this->error("Backup file '{$backupPath}' is invalid or empty.");

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->warn("WARNING: This will overwrite the current database with contents from '{$backupPath}'.");
            if (! $this->confirm('Are you sure you want to proceed with the database restore?')) {
                $this->info('Database restore cancelled.');
                if ($tempFileToDelete && file_exists($tempFileToDelete)) {
                    unlink($tempFileToDelete);
                }

                return self::SUCCESS;
            }
        }

        $connectionName = config('database.default', 'mysql');
        $connectionConfig = config("database.connections.{$connectionName}", []);

        $this->info("Restoring database ({$connectionName}) from '{$backupPath}'...");

        try {
            $success = $this->executeRestore($connectionName, $connectionConfig, $backupPath);
            if (! $success) {
                $this->error('Failed to restore database.');

                return self::FAILURE;
            }

            $this->info('Database restored successfully.');

            // Clear cache
            Artisan::call('optimize:clear');
            $this->info('Application caches cleared.');

            return self::SUCCESS;
        } finally {
            if ($tempFileToDelete && file_exists($tempFileToDelete)) {
                unlink($tempFileToDelete);
            }
        }
    }

    private function getLatestS3BackupKey(string $diskName): ?string
    {
        $files = Storage::disk($diskName)->files('backups');
        $backupFiles = array_filter($files, fn ($f) => str_ends_with($f, '.sql.gz'));
        if (empty($backupFiles)) {
            return null;
        }
        rsort($backupFiles);

        return $backupFiles[0];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function executeRestore(string $connectionName, array $config, string $backupPath): bool
    {
        if ($connectionName === 'sqlite') {
            return $this->restoreSqlite($config, $backupPath);
        }

        return $this->restoreMysql($config, $backupPath);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function restoreSqlite(array $config, string $backupPath): bool
    {
        $gz = gzopen($backupPath, 'rb');
        if ($gz === false) {
            return false;
        }

        $sql = '';
        while (! gzeof($gz)) {
            $sql .= gzread($gz, 4096);
        }
        gzclose($gz);

        $dbPath = $config['database'] ?? '';
        if ($dbPath === ':memory:' || empty($dbPath)) {
            DB::unprepared($sql);

            return true;
        }

        // If file-based sqlite, check if contents are raw database or SQL script
        if (str_starts_with($sql, 'SQLite format 3')) {
            return file_put_contents($dbPath, $sql) !== false;
        }

        DB::unprepared($sql);

        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function restoreMysql(array $config, string $backupPath): bool
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? '3306');
        $database = $config['database'] ?? 'cyf';
        $username = $config['username'] ?? 'cyf';
        $password = $config['password'] ?? '';

        $env = [];
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $command = "gunzip -c \"{$backupPath}\" | mysql --host=\"{$host}\" --port=\"{$port}\" --user=\"{$username}\" \"{$database}\"";

        $process = Process::fromShellCommandline($command, null, $env);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('mysql import failed: '.$process->getErrorOutput());

            return false;
        }

        return true;
    }
}
