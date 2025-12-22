<?php

namespace Tests\Feature\Filament\Admin\Reports;

use App\Filament\Admin\Resources\ReportsResource\Pages\BookReports;
use App\Livewire\ReportTable;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'staff']); // Create other roles if necessary for full coverage

        $this->actingAs(User::factory()->create([
            'role_id' => $adminRole->id,
        ]));
    }

    public function test_book_reports_page_can_be_rendered(): void
    {
        $this->get(BookReports::getUrl())
            ->assertSuccessful();
    }

    public function test_most_borrowed_books_table_can_be_filtered_by_genre(): void
    {
        $genre1 = Genre::factory()->create(['name' => 'Fiction']);
        $genre2 = Genre::factory()->create(['name' => 'Science']);

        $book1 = Book::factory()->create(['genre_id' => $genre1->id]);
        $book2 = Book::factory()->create(['genre_id' => $genre1->id]);
        $book3 = Book::factory()->create(['genre_id' => $genre2->id]);

        // Simulate borrowing for book1 (Fiction)
        \App\Models\Transaction::factory()->create([
            'user_id' => User::factory()->create()->id,
        ])->items()->create([
            'book_id' => $book1->id,
            'returned_date' => now(),
        ]);
        \App\Models\Transaction::factory()->create([
            'user_id' => User::factory()->create()->id,
        ])->items()->create([
            'book_id' => $book1->id,
            'returned_date' => now(),
        ]);

        // Simulate borrowing for book2 (Fiction)
        \App\Models\Transaction::factory()->create([
            'user_id' => User::factory()->create()->id,
        ])->items()->create([
            'book_id' => $book2->id,
            'returned_date' => now(),
        ]);

        // Simulate borrowing for book3 (Science)
        \App\Models\Transaction::factory()->create([
            'user_id' => User::factory()->create()->id,
        ])->items()->create([
            'book_id' => $book3->id,
            'returned_date' => now(),
        ]);


        Livewire::test(ReportTable::class, [
            'tableId' => 'most-borrowed',
            'parentId' => (new BookReports())->getId(),
        ])
            ->set('tableFilters.genre.value', $genre1->id)
            ->assertCanSeeTableRecords([$book1, $book2])
            ->assertDontSeeTableRecords([$book3]);
    }
}
