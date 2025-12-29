<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RealBookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        Book::truncate();
        Author::truncate();
        Genre::truncate();
        Publisher::truncate();

        Schema::enableForeignKeyConstraints();

        $queries = [
            'laravel', 'php', 'vuejs', 'livewire',
            'fiction', 'science fiction', 'fantasy', 'mystery',
            'biography', 'history',
        ];

        $totalBooks = 0;
        $maxBooks = 100;

        foreach ($queries as $query) {
            if ($totalBooks >= $maxBooks) {
                break;
            }

            for ($i = 0; $i < 3; $i++) { // 3 pages of results
                if ($totalBooks >= $maxBooks) {
                    break;
                }

                try {
                    $startIndex = $i * 40;
                    $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
                        'q' => $query,
                        'maxResults' => 40,
                        'startIndex' => $startIndex,
                    ]);

                    if ($response->failed()) {
                        Log::error('Failed to fetch books from Google Books API', ['query' => $query, 'response' => $response->body()]);
                        continue;
                    }

                    $books = $response->json()['items'] ?? [];

                    if (empty($books)) {
                        break; // No more results for this query
                    }

                    foreach ($books as $bookData) {
                        if ($totalBooks >= $maxBooks) {
                            break;
                        }

                        $volumeInfo = $bookData['volumeInfo'];

                        // Skip if essential data is missing
                        if (empty($volumeInfo['authors'][0]) || empty($volumeInfo['publisher']) || empty($volumeInfo['categories'][0]) || empty($volumeInfo['industryIdentifiers'])) {
                            continue;
                        }

                        $isbn = null;
                        foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                            if ($identifier['type'] === 'ISBN_13') {
                                $isbn = $identifier['identifier'];
                                break;
                            }
                            if ($identifier['type'] === 'ISBN_10') {
                                $isbn = $identifier['identifier'];
                            }
                        }

                        if (!$isbn || Book::where('isbn', $isbn)->exists()) {
                            continue;
                        }

                        $publisher = Publisher::firstOrCreate(
                            ['name' => $volumeInfo['publisher']],
                            ['founded' => now()->subYears(rand(5, 100))->format('Y-m-d')]
                        );

                        $author = Author::firstOrCreate(
                            ['name' => $volumeInfo['authors'][0]],
                            [
                                'publisher_id' => $publisher->id,
                                'date_of_birth' => now()->subYears(rand(25, 80))->format('Y-m-d'),
                                'bio' => $volumeInfo['description'] ?? 'No bio available.',
                            ]
                        );

                        $genre = Genre::firstOrCreate(
                            ['name' => $volumeInfo['categories'][0]],
                            [
                                'bg_color' => fake()->hexColor(),
                                'text_color' => fake()->hexColor(),
                            ]
                        );

                        $publishedDate = !empty($volumeInfo['publishedDate']) ? Str::limit(str_replace('-', '/', $volumeInfo['publishedDate']), 10, '') : '2023-01-01';
                        try {
                            $publishedDate = \Carbon\Carbon::parse($publishedDate)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $publishedDate = now()->subYears(rand(1, 20))->format('Y-m-d');
                        }


                        Book::create([
                            'author_id' => $author->id,
                            'publisher_id' => $publisher->id,
                            'genre_id' => $genre->id,
                            'title' => $volumeInfo['title'],
                            'cover_image' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
                            'isbn' => $isbn,
                            'price' => ($bookData['saleInfo']['listPrice']['amount'] ?? rand(10, 100)) * 100,
                            'description' => $volumeInfo['description'] ?? 'No description available.',
                            'stock' => rand(5, 50),
                            'available' => true,
                            'published' => $publishedDate,
                        ]);

                        $totalBooks++;
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing book data', ['exception' => $e->getMessage()]);
                    continue;
                }
            }
        }

        $this->command->info("Successfully seeded {$totalBooks} books.");
    }
}

