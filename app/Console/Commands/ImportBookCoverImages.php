<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportBookCoverImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-book-cover-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import book cover images from existing URLs into the Spatie Media Library.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting the import of book cover images...');

        $booksToImport = Book::whereNotNull('cover_image')
            ->whereDoesntHave('media', function ($query) {
                $query->where('collection_name', 'coverBooks');
            })
            ->get();

        if ($booksToImport->isEmpty()) {
            $this->info('No books found that need image importing.');
            return;
        }

        $progressBar = $this->output->createProgressBar($booksToImport->count());
        $progressBar->start();

        foreach ($booksToImport as $book) {
            try {
                if (filter_var($book->cover_image, FILTER_VALIDATE_URL)) {
                    $book->addMediaFromUrl($book->cover_image)
                        ->toMediaCollection('coverBooks');
                } else {
                    $this->warn("Skipping invalid URL for book ID {$book->id}: {$book->cover_image}");
                }
            } catch (\Exception $e) {
                $this->error("Failed to import image for book ID {$book->id} from URL: {$book->cover_image}");
                Log::error('Image import failed: ' . $e->getMessage());
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nImage import completed.");
    }
}
