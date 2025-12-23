<?php

namespace App\Console\Commands;

use App\Enums\BorrowedStatus;
use App\Mail\OverdueNoticeMail;
use App\Models\Transaction;
use App\Models\User;
use App\Settings\FeeSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOverdueNotices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-overdue-notices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans for overdue transactions and sends email notifications to users.';

    /**
     * Execute the console command.
     */
    public function handle(FeeSettings $feeSettings)
    {
        if (!$feeSettings->send_overdue_notifications) {
            $this->info('Overdue notifications are disabled. Exiting.');
            return 0;
        }

        $this->info('Checking for overdue transactions...');

        // We get all transactions that are overdue and group them by user.
        $overdueTransactions = Transaction::query()
            ->where('status', BorrowedStatus::Borrowed)
            ->where('due_date', '<', now())
            ->with('user')
            ->get()
            ->groupBy('user_id');

        if ($overdueTransactions->isEmpty()) {
            $this->info('No overdue transactions found.');
            return 0;
        }

        $this->info("Found {$overdueTransactions->count()} user(s) with overdue items.");
        
        $emailsSent = 0;

        foreach ($overdueTransactions as $userId => $userTransactions) {
            $user = $userTransactions->first()->user;

            if (!$user || !$user->email) {
                $this->warn("User with ID {$userId} has no email address. Skipping.");
                continue;
            }

            try {
                Mail::to($user)->send(new OverdueNoticeMail($user, $userTransactions));
                $this->info("Sent overdue notice to: {$user->email}");
                $emailsSent++;
            } catch (\Exception $e) {
                $this->error("Failed to send email to {$user->email}: " . $e->getMessage());
                Log::error("Failed to send overdue notice to user {$userId}: " . $e->getMessage());
            }
        }
        
        $this->info("Successfully sent {$emailsSent} overdue notices.");

        return 0;
    }
}
