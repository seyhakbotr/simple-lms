<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application\'s database.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, MembershipTypeSeeder::class]);

        $admin = User::factory()
            ->role("admin")
            ->create([
                "name" => "Admin",
                "email" => "admin@gmail.com",
                "password" => "developer",
            ]);

        $staffs = User::factory(2)
            ->role("staff")
            ->state(
                new Sequence(
                    [
                        "name" => "Catharine McCall",
                        "email" => "catherine@gmail.com",
                        "password" => "staff001",
                    ],
                    [
                        "name" => "Lina Carter",
                        "email" => "lina@gmail.com",
                        "password" => "staff002",
                    ],
                ),
            )
            ->create();

        // Get membership types
        $membershipTypes = \App\Models\MembershipType::all();

        $users = User::factory(7)
            ->role("borrower")
            ->create()
            ->each(function ($user) use ($membershipTypes) {
                // Assign random membership type to each borrower
                $membershipType = $membershipTypes->random();
                $startDate = now()->subMonths(rand(1, 6));

                $user->update([
                    "membership_type_id" => $membershipType->id,
                    "membership_started_at" => $startDate,
                    "membership_expires_at" => $startDate
                        ->copy()
                        ->addMonths(
                            $membershipType->membership_duration_months,
                        ),
                ]);
            });

        $publishersData = [
            ['name' => 'Penguin Random House', 'founded' => '1927-01-01'],
            ['name' => 'Hachette Livre', 'founded' => '1826-01-01'],
            ['name' => 'HarperCollins', 'founded' => '1817-01-01'],
            ['name' => 'Scholastic Corporation', 'founded' => '1920-01-01'],
            ['name' => 'Bantam Books', 'founded' => '1945-01-01'],
            ['name' => 'Vintage Books', 'founded' => '1954-01-01'],
            ['name' => 'Anchor Books', 'founded' => '1940-01-01'],
        ];

        $publishers = [];
        foreach ($publishersData as $data) {
            $publishers[$data['name']] = Publisher::firstOrCreate(
                ['name' => $data['name']],
                ['founded' => $data['founded']]
            );
        }

        $authorsData = [
            ['name' => 'Stephen King', 'date_of_birth' => '1947-09-21', 'bio' => 'Master of horror.'],
            ['name' => 'J.K. Rowling', 'date_of_birth' => '1965-07-31', 'bio' => 'Creator of the Harry Potter series.'],
            ['name' => 'George R.R. Martin', 'date_of_birth' => '1948-09-20', 'bio' => 'Author of A Song of Ice and Fire.'],
            ['name' => 'Haruki Murakami', 'date_of_birth' => '1949-01-12', 'bio' => 'Japanese contemporary novelist.'],
            ['name' => 'Margaret Atwood', 'date_of_birth' => '1939-11-18', 'bio' => 'Acclaimed Canadian writer.'],
        ];

        $authors = [];
        foreach ($authorsData as $data) {
            $randomPublisher = $publishers[array_rand($publishers)]; // Get a random publisher object
            $authors[$data['name']] = Author::firstOrCreate(
                ['name' => $data['name']],
                ['date_of_birth' => $data['date_of_birth'], 'bio' => $data['bio'], 'publisher_id' => $randomPublisher->id]
            );
        }

        $genresData = [
            ['name' => 'Horror', 'bg_color' => '#000000', 'text_color' => '#FFFFFF'],
            ['name' => 'Fantasy', 'bg_color' => '#8B4513', 'text_color' => '#FFFFFF'],
            ['name' => 'Literary Fiction', 'bg_color' => '#A9A9A9', 'text_color' => '#000000'],
            ['name' => 'Dystopian', 'bg_color' => '#4B0082', 'text_color' => '#FFFFFF'],
            ['name' => 'Science Fiction', 'bg_color' => '#00008B', 'text_color' => '#FFFFFF'],
            ['name' => 'Mystery', 'bg_color' => '#2F4F4F', 'text_color' => '#FFFFFF'],
            ['name' => 'Thriller', 'bg_color' => '#8B0000', 'text_color' => '#FFFFFF'],
            ['name' => 'Romance', 'bg_color' => '#FF69B4', 'text_color' => '#FFFFFF'],
        ];

        $genres = [];
        foreach ($genresData as $data) {
            $genres[$data['name']] = Genre::firstOrCreate(
                ['name' => $data['name']],
                ['bg_color' => $data['bg_color'], 'text_color' => $data['text_color']]
            );
        }

        $booksData = [
            [
                'title' => 'The Shining',
                'author' => 'Stephen King',
                'publisher' => 'Penguin Random House',
                'genre' => 'Horror',
                'isbn' => '978-0385121675',
                'price' => 12.99,
                'description' => 'A classic horror novel.',
                'stock' => 50,
                'available' => 45,
                'published' => '1977-01-28',
            ],
            [
                'title' => 'Harry Potter and the Sorcerer\'s Stone',
                'author' => 'J.K. Rowling',
                'publisher' => 'Scholastic Corporation',
                'genre' => 'Fantasy',
                'isbn' => '978-0590353403',
                'price' => 10.50,
                'description' => 'The first book in the Harry Potter series.',
                'stock' => 100,
                'available' => 90,
                'published' => '1997-06-26',
            ],
            [
                'title' => 'A Game of Thrones',
                'author' => 'George R.R. Martin',
                'publisher' => 'Bantam Books',
                'genre' => 'Fantasy',
                'isbn' => '978-0553103540',
                'price' => 15.00,
                'description' => 'First novel in A Song of Ice and Fire series.',
                'stock' => 75,
                'available' => 70,
                'published' => '1996-08-06',
            ],
            [
                'title' => 'Norwegian Wood',
                'author' => 'Haruki Murakami',
                'publisher' => 'Vintage Books',
                'genre' => 'Literary Fiction',
                'isbn' => '978-0375704023',
                'price' => 13.99,
                'description' => 'A poignant story of loss and sexuality.',
                'stock' => 60,
                'available' => 55,
                'published' => '1987-09-04',
            ],
            [
                'title' => 'The Handmaid\'s Tale',
                'author' => 'Margaret Atwood',
                'publisher' => 'Anchor Books',
                'genre' => 'Dystopian',
                'isbn' => '978-0385490818',
                'price' => 9.99,
                'description' => 'A dystopian novel of a totalitarian society.',
                'stock' => 80,
                'available' => 75,
                'published' => '1985-01-01',
            ],
        ];

        foreach ($booksData as $data) {
            Book::firstOrCreate(
                ['title' => $data['title']],
                [
                    'author_id' => $authors[$data['author']]->id,
                    'publisher_id' => $publishers[$data['publisher']]->id,
                    'genre_id' => $genres[$data['genre']]->id,
                    'isbn' => $data['isbn'],
                    'price' => $data['price'],
                    'description' => $data['description'],
                    'stock' => $data['stock'],
                    'available' => $data['available'],
                    'published' => $data['published'],
                ]
            );
        }

        // Create transactions - the factory will automatically create transaction items
        $transactions = Transaction::factory(10)->recycle($users)->create();
    }
}