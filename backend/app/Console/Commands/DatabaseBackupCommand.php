<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseBackupCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'db:backup
                            {--disk=s3 : The storage disk to upload the backup to}
                            {--no-upload : Skip uploading to cloud storage}
                            {--path= : Custom local directory to store the backup}
                            {--retention=7 : Number of days to retain local backups}';

    /**
     * @var string
     */
    protected $description = 'Create a compressed database backup and upload it to S3 object storage';

    public function handle(): int
    {
        $this->info('Starting database backup routine...');

        $timestamp = date('Ymd_His');
        $filename = "cyf_db_{$timestamp}.sql.gz";

        $localDir = (string) ($this->option('path') ?: storage_path('backups'));
        if (! is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }

        $localPath = rtrim($localDir, '/').'/'.$filename;
        $connectionName = config('database.default', 'mysql');
        $connectionConfig = config("database.connections.{$connectionName}", []);

        // 1. Generate dump
        $success = $this->createDump($connectionName, $connectionConfig, $localPath);
        if (! $success || ! file_exists($localPath)) {
            $this->error('Failed to create database dump.');

            return self::FAILURE;
        }

        $fileSize = round(filesize($localPath) / 1024, 2);
        $this->info("Database backup created successfully: {$localPath} ({$fileSize} KB)");

        // 2. Upload to Cloud Storage (S3)
        if (! $this->option('no-upload')) {
            $diskName = (string) $this->option('disk');
            $s3Key = "backups/{$filename}";

            $this->info("Uploading backup to storage disk '{$diskName}' at '{$s3Key}'...");

            try {
                $stream = fopen($localPath, 'r');
                if ($stream === false) {
                    throw new \RuntimeException("Unable to open local backup file: {$localPath}");
                }

                $uploaded = Storage::disk($diskName)->put($s3Key, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($uploaded) {
                    $this->info("Cloud backup completed: {$diskName}://{$s3Key}");
                } else {
                    $this->warn("Cloud upload returned false on disk '{$diskName}'.");
                }
            } catch (\Throwable $e) {
                $this->error("Failed to upload backup to disk '{$diskName}': ".$e->getMessage());
                // In production, we don't abort local success, but signal warning
            }
        }

        // 3. Prune old local backups
        $retentionDays = (int) $this->option('retention');
        $this->pruneLocalBackups($localDir, $retentionDays);

        $this->info('Backup routine completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createDump(string $connectionName, array $config, string $outputPath): bool
    {
        if ($connectionName === 'sqlite') {
            return $this->createSqliteDump($config, $outputPath);
        }

        return $this->createMysqlDump($config, $outputPath);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createSqliteDump(array $config, string $outputPath): bool
    {
        $dbPath = $config['database'] ?? '';
        if ($dbPath === ':memory:' || empty($dbPath)) {
            // For in-memory sqlite databases, dump schema and records via PDO
            $tempSql = tempnam(sys_get_temp_dir(), 'cyf_sql_');
            if ($tempSql === false) {
                return false;
            }

            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $sql = "PRAGMA foreign_keys = OFF;\n";
            foreach ($tables as $table) {
                $tableName = $table->name;
                $createSql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$tableName]);
                if ($createSql && isset($createSql->sql)) {
                    $sql .= "DROP TABLE IF EXISTS \"{$tableName}\";\n";
                    $sql .= $createSql->sql.";\n";
                    $rows = DB::table($tableName)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $cols = implode(', ', array_keys($rowArray));
                        $vals = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : "'".addslashes((string) $v)."'", array_values($rowArray)));
                        $sql .= "INSERT INTO {$tableName} ({$cols}) VALUES ({$vals});\n";
                    }
                }
            }
            $sql .= "PRAGMA foreign_keys = ON;\n";

            file_put_contents($tempSql, $sql);
            $gzData = gzencode(file_get_contents($tempSql), 9);
            unlink($tempSql);
            if ($gzData === false) {
                return false;
            }

            return file_put_contents($outputPath, $gzData) !== false;
        }

        if (! file_exists($dbPath)) {
            return false;
        }

        $gzData = gzencode((string) file_get_contents($dbPath), 9);
        if ($gzData === false) {
            return false;
        }

        return file_put_contents($outputPath, $gzData) !== false;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createMysqlDump(array $config, string $outputPath): bool
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? '3306');
        $database = $config['database'] ?? 'cyf';
        $username = $config['username'] ?? 'cyf';
        $password = $config['password'] ?? '';

        $cmd = [
            'mysqldump',
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            $database,
        ];

        $env = [];
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = new Process($cmd, null, $env);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('mysqldump failed: '.$process->getErrorOutput());

            return false;
        }

        $gzData = gzencode($process->getOutput(), 9);
        if ($gzData === false) {
            return false;
        }

        return file_put_contents($outputPath, $gzData) !== false;
    }

    private function pruneLocalBackups(string $dir, int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $cutoff = time() - ($days * 86400);
        $files = glob($dir.'/cyf_db_*.sql.gz');
        if (! is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
