<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
     * Seed the application's database.
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

        $publishers = Publisher::factory(10)->create();

        $authors = Author::factory(10)->recycle($publishers)->create();

        $genres = Genre::factory(10)->create();

        // Create more books to avoid conflicts when seeding transactions
        $books = Book::factory(30)
            ->recycle($publishers)
            ->recycle($authors)
            ->recycle($genres)
            ->create();

        // Create transactions - the factory will automatically create transaction items
        $transactions = Transaction::factory(10)->recycle($users)->create();
    }
}
