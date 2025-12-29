<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportBooks extends Command
{
    protected $signature = 'app:import-books
        {path : Path to a CSV or JSON file}
        {--delimiter=, : CSV delimiter}
        {--dry-run : Validate and report without writing to the database}';

    protected $description = 'Import books (with publisher, genre, and author) from a CSV or JSON file.';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $delimiter = (string) $this->option('delimiter');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        $rows = match ($extension) {
            'json' => $this->readJson($path),
            'csv' => $this->readCsv($path, $delimiter),
            default => null,
        };

        if ($rows === null) {
            $this->error('Unsupported file type. Please provide a .csv or .json file.');
            return self::FAILURE;
        }

        if (count($rows) === 0) {
            $this->warn('No rows found to import.');
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $line = $index + 1;
                $row = $this->normalizeRow($row);

                $validationError = $this->validateRow($row);
                if ($validationError !== null) {
                    $this->warn("Row {$line} skipped: {$validationError}");
                    $skipped++;
                    continue;
                }

                $publisher = Publisher::query()->firstOrCreate(
                    ['name' => $row['publisher_name']],
                    ['founded' => $row['publisher_founded']]
                );

                $genre = Genre::query()->firstOrCreate(
                    ['name' => $row['genre_name']],
                    [
                        'bg_color' => $row['genre_bg_color'] ?? '#ffffff',
                        'text_color' => $row['genre_text_color'] ?? '#000000',
                    ]
                );

                $author = Author::query()->firstOrCreate(
                    [
                        'publisher_id' => $publisher->id,
                        'name' => $row['author_name'],
                    ],
                    [
                        'date_of_birth' => $row['author_date_of_birth'],
                        'bio' => $row['author_bio'],
                    ]
                );

                $attributes = [
                    'author_id' => $author->id,
                    'publisher_id' => $publisher->id,
                    'genre_id' => $genre->id,
                    'title' => $row['title'],
                    'isbn' => $row['isbn'],
                    'price' => $row['price'],
                    'description' => $row['description'],
                    'stock' => $row['stock'],
                    'available' => $row['available'],
                    'published' => $row['published'],
                ];

                $existing = Book::query()->where('isbn', $row['isbn'])->first();

                if ($dryRun) {
                    $existing ? $updated++ : $created++;
                    continue;
                }

                $book = Book::query()->updateOrCreate(
                    ['isbn' => $row['isbn']],
                    Arr::except($attributes, ['isbn'])
                );

                $book->wasRecentlyCreated ? $created++ : $updated++;
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry run completed. No changes were saved.');
            } else {
                DB::commit();
            }

            $this->info("Imported. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $path): array
    {
        $contents = file_get_contents($path);
        $decoded = json_decode($contents ?: '', true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($row) => is_array($row)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(string $path, string $delimiter): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = null;
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($v) => Str::snake(trim((string) $v)), $data);
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $publisherName = (string) ($row['publisher'] ?? $row['publisher_name'] ?? '');
        $genreName = (string) ($row['genre'] ?? $row['genre_name'] ?? '');
        $authorName = (string) ($row['author'] ?? $row['author_name'] ?? '');

        $availableRaw = $row['available'] ?? false;
        $available = filter_var($availableRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($available === null) {
            $available = false;
        }

        $publishedRaw = $row['published'] ?? null;
        $published = null;
        if ($publishedRaw !== null && $publishedRaw !== '') {
            $published = Carbon::parse((string) $publishedRaw)->toDateString();
        }

        $publisherFoundedRaw = $row['publisher_founded'] ?? $row['founded'] ?? null;
        $publisherFounded = null;
        if ($publisherFoundedRaw !== null && $publisherFoundedRaw !== '') {
            $publisherFounded = Carbon::parse((string) $publisherFoundedRaw)->toDateString();
        }

        $authorDobRaw = $row['author_date_of_birth'] ?? $row['author_dob'] ?? $row['date_of_birth'] ?? null;
        $authorDob = null;
        if ($authorDobRaw !== null && $authorDobRaw !== '') {
            $authorDob = Carbon::parse((string) $authorDobRaw)->toDateString();
        }

        return [
            'isbn' => trim((string) ($row['isbn'] ?? '')),
            'title' => trim((string) ($row['title'] ?? '')),
            'description' => $row['description'] ?? null,
            'price' => $row['price'] ?? null,
            'stock' => $row['stock'] ?? null,
            'available' => $available,
            'published' => $published,

            'publisher_name' => trim($publisherName),
            'publisher_founded' => $publisherFounded,

            'genre_name' => trim($genreName),
            'genre_bg_color' => $row['genre_bg_color'] ?? $row['bg_color'] ?? null,
            'genre_text_color' => $row['genre_text_color'] ?? $row['text_color'] ?? null,

            'author_name' => trim($authorName),
            'author_date_of_birth' => $authorDob,
            'author_bio' => $row['author_bio'] ?? $row['bio'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateRow(array $row): ?string
    {
        if ($row['isbn'] === '') {
            return 'isbn is required';
        }

        if ($row['title'] === '') {
            return 'title is required';
        }

        if ($row['publisher_name'] === '') {
            return 'publisher_name is required';
        }

        if ($row['genre_name'] === '') {
            return 'genre_name is required';
        }

        if ($row['author_name'] === '') {
            return 'author_name is required';
        }

        if ($row['price'] === null || $row['price'] === '') {
            return 'price is required';
        }

        if (!is_numeric($row['price'])) {
            return 'price must be numeric (dollars)';
        }

        if ($row['stock'] === null || $row['stock'] === '') {
            return 'stock is required';
        }

        if (!is_numeric($row['stock'])) {
            return 'stock must be numeric';
        }

        if ($row['published'] === null || $row['published'] === '') {
            return 'published is required (date)';
        }

        return null;
    }
}
