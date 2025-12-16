<?php

namespace Database\Seeders;

use App\Models\MembershipType;
use Illuminate\Database\Seeder;

class MembershipTypeSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $membershipTypes = [
            [
                "name" => "Basic",
                "description" => "Basic membership for general public",
                "max_books_allowed" => 3,
                "max_borrow_days" => 14,
                "renewal_limit" => 1,
                "fine_rate" => 10.0,
                "membership_duration_months" => 12,
                "membership_fee" => 20.0,
                "is_active" => true,
            ],
            [
                "name" => "Premium",
                "description" => "Premium membership with extended privileges",
                "max_books_allowed" => 10,
                "max_borrow_days" => 30,
                "renewal_limit" => 3,
                "fine_rate" => 5.0,
                "membership_duration_months" => 12,
                "membership_fee" => 100.0,
                "is_active" => true,
            ],
            [
                "name" => "Student",
                "description" => "Discounted membership for students",
                "max_books_allowed" => 5,
                "max_borrow_days" => 21,
                "renewal_limit" => 2,
                "fine_rate" => 5.0,
                "membership_duration_months" => 12,
                "membership_fee" => 10.0,
                "is_active" => true,
            ],
            [
                "name" => "Faculty",
                "description" => "Faculty and staff membership",
                "max_books_allowed" => 15,
                "max_borrow_days" => 60,
                "renewal_limit" => 5,
                "fine_rate" => 0.0,
                "membership_duration_months" => 12,
                "membership_fee" => 0.0,
                "is_active" => true,
            ],
            [
                "name" => "Lifetime",
                "description" =>
                    "Lifetime membership with unlimited privileges",
                "max_books_allowed" => 20,
                "max_borrow_days" => 90,
                "renewal_limit" => 10,
                "fine_rate" => 0.0,
                "membership_duration_months" => 1200, // 100 years
                "membership_fee" => 500.0,
                "is_active" => true,
            ],
        ];

        foreach ($membershipTypes as $type) {
            MembershipType::updateOrCreate(["name" => $type["name"]], $type);
        }
    }
}
