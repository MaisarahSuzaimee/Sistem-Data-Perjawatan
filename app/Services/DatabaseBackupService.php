<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use PDO;
use Throwable;

class DatabaseBackupService
{
    /**
     * Create a compressed SQL backup and return its metadata.
     */
    public function create(): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException(
                'This backup service currently supports MySQL only.'
            );
        }

        $disk = Storage::disk('local');

        $directory = 'database-backups';

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $filename = 'mystaff_' .
            now('Asia/Kuala_Lumpur')->format('Y-m-d_H-i-s') .
            '.sql.gz';

        $relativePath = "{$directory}/{$filename}";
        $absolutePath = storage_path("app/private/{$relativePath}");

        // Laravel's local disk may use a different root.
        $absolutePath = $disk->path($relativePath);

        $output = gzopen($absolutePath, 'wb6');

        if ($output === false) {
            throw new RuntimeException(
                'Unable to create the compressed backup file.'
            );
        }

        try {
            $pdo = $this->makePdoConnection($config);

            $this->write($output, "-- MySTAFF database backup\n");
            $this->write($output, '-- Created at: ' .
                now('Asia/Kuala_Lumpur')->toDateTimeString() . "\n\n");

            $this->write($output, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query(
                "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $this->exportTable($pdo, $output, $table);
            }

            $this->write($output, "\nSET FOREIGN_KEY_CHECKS=1;\n");

            gzclose($output);
            $output = null;

            clearstatcache(true, $absolutePath);

            if (! is_file($absolutePath) || filesize($absolutePath) === 0) {
                throw new RuntimeException(
                    'The backup file was created but is empty.'
                );
            }

            // Verify that the gzip file can be read.
            $verify = gzopen($absolutePath, 'rb');

            if ($verify === false) {
                throw new RuntimeException(
                    'The generated gzip file could not be opened.'
                );
            }

            $firstLine = gzgets($verify);
            gzclose($verify);

            if ($firstLine === false) {
                throw new RuntimeException(
                    'The generated backup could not be verified.'
                );
            }

            return [
                'filename' => $filename,
                'path' => $relativePath,
                'size' => filesize($absolutePath),
                'created_at' => now('Asia/Kuala_Lumpur'),
            ];
        } catch (Throwable $exception) {
            if (is_resource($output)) {
                gzclose($output);
            }

            $disk->delete($relativePath);

            throw $exception;
        }
    }

    private function makePdoConnection(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database'],
            $config['charset'] ?? 'utf8mb4',
        );

        return new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ],
        );
    }

    private function exportTable(
        PDO $pdo,
        $output,
        string $table,
    ): void {
        $quotedTable = $this->quoteIdentifier($table);

        $create = $pdo->query(
            "SHOW CREATE TABLE {$quotedTable}"
        )->fetch(PDO::FETCH_NUM);

        if (! isset($create[1])) {
            throw new RuntimeException(
                "Could not read the structure of table {$table}."
            );
        }

        $this->write($output, "\n-- Table: {$table}\n");
        $this->write($output, "DROP TABLE IF EXISTS {$quotedTable};\n");
        $this->write($output, $create[1] . ";\n\n");

        $columns = $pdo->query(
            "SHOW FULL COLUMNS FROM {$quotedTable}"
        )->fetchAll(PDO::FETCH_ASSOC);

        $columnNames = array_column($columns, 'Field');
        $binaryTypes = [];

        foreach ($columns as $column) {
            $type = strtolower((string) $column['Type']);

            if (
                str_contains($type, 'binary') ||
                str_contains($type, 'blob') ||
                str_contains($type, 'bit')
            ) {
                $binaryTypes[$column['Field']] = true;
            }
        }

        $statement = $pdo->query(
            "SELECT * FROM {$quotedTable}"
        );

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $values = [];

            foreach ($columnNames as $column) {
                $value = $row[$column];

                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (isset($binaryTypes[$column])) {
                    $values[] = '0x' . bin2hex($value);
                } else {
                    $quoted = $pdo->quote((string) $value);

                    if ($quoted === false) {
                        throw new RuntimeException(
                            "Could not encode a value from {$table}."
                        );
                    }

                    $values[] = $quoted;
                }
            }

            $this->write(
                $output,
                "INSERT INTO {$quotedTable} (" .
                implode(', ', array_map(
                    fn ($column) => $this->quoteIdentifier($column),
                    $columnNames
                )) .
                ') VALUES (' . implode(', ', $values) . ");\n"
            );
        }

        $statement->closeCursor();

        $this->write($output, "\n");
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function write($output, string $content): void
    {
        if (gzwrite($output, $content) === false) {
            throw new RuntimeException(
                'Failed while writing the database backup.'
            );
        }
    }
}
