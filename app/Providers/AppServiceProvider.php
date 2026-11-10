<?php

namespace App\Providers;

use App\Contracts\Chat\AIProviderInterface;
use App\Contracts\Chat\DocumentIngestionInterface;
use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Contracts\Chat\VectorSearchInterface;
use App\Services\Chat\ChatService;
use App\Services\Chat\GeminiAIProvider;
use App\Services\Chat\GeminiEmbeddingProvider;
use App\Services\Chat\QdrantDocumentIngestion;
use App\Services\Chat\QdrantVectorSearch;
use App\Services\Chat\UnconfiguredAIProvider;
use App\Services\Chat\UnconfiguredDocumentIngestion;
use App\Services\Chat\UnconfiguredEmbeddingProvider;
use App\Services\Chat\UnconfiguredVectorSearch;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QdrantVectorSearch::class);

        $this->app->bind(AIProviderInterface::class, function (Application $app): AIProviderInterface {
            return config('dora.llm.provider') === 'gemini'
                ? $app->make(GeminiAIProvider::class)
                : $app->make(UnconfiguredAIProvider::class);
        });
        $this->app->bind(EmbeddingProviderInterface::class, function (Application $app): EmbeddingProviderInterface {
            return config('dora.embedding.provider') === 'gemini'
                ? $app->make(GeminiEmbeddingProvider::class)
                : $app->make(UnconfiguredEmbeddingProvider::class);
        });
        $this->app->bind(VectorSearchInterface::class, function (Application $app): VectorSearchInterface {
            return config('dora.vector.provider') === 'qdrant'
                ? $app->make(QdrantVectorSearch::class)
                : $app->make(UnconfiguredVectorSearch::class);
        });
        $this->app->bind(DocumentIngestionInterface::class, function (Application $app): DocumentIngestionInterface {
            return config('dora.embedding.provider') === 'gemini'
                && config('dora.vector.provider') === 'qdrant'
                    ? $app->make(QdrantDocumentIngestion::class)
                    : $app->make(UnconfiguredDocumentIngestion::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('dora-chat', function (Request $request): Limit {
            $user = $request->user();
            $key = $user ? 'user:'.$user->getAuthIdentifier() : 'ip:'.$request->ip();

            return Limit::perMinute($user ? 20 : 10)->by($key);
        });

        View::composer('components.chat-assistant', function (ViewInstance $view): void {
            $view->with('aiAvailable', app(ChatService::class)->isConfigured());
        });
    }
}
