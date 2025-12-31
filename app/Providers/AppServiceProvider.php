<?php

namespace App\Providers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Author::class, UserPolicy::class);
        Gate::policy(Publisher::class, UserPolicy::class);
        Gate::policy(Genre::class, UserPolicy::class);
        Gate::policy(Book::class, UserPolicy::class);
        Gate::policy(Transaction::class, UserPolicy::class);

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        // Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
