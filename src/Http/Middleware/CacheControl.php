<?php

namespace Laravel\Telescope\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CacheControl
{
    /**
     * Default cache duration in seconds
     */
    protected $defaultTtl;

    /**
     * Route-specific cache TTL configurations
     */
    protected $routeTtl = [
        'telescope-api/requests' => 300,      // 5 minutes
        'telescope-api/commands' => 600,      // 10 minutes
        'telescope-api/schedule' => 300,      // 5 minutes
        'telescope-api/cache' => 300,         // 5 minutes
        'telescope-api/queries' => 300,       // 5 minutes
        'telescope-api/models' => 300,        // 5 minutes
        'telescope-api/views' => 300,         // 5 minutes
        'telescope-api/redis' => 300,         // 5 minutes
        'telescope-api/client-requests' => 300, // 5 minutes
        'telescope-api/monitored-tags' => 600,  // 10 minutes
    ];

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Get default TTL from environment variable or fallback to 1 hour
        $this->defaultTtl = env('TELESCOPE_CACHE_TTL', 3600);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $ttl  Optional TTL override in seconds
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?int $ttl = null)
    {
        $response = $next($request);

        // Only apply cache headers to GET requests
        if (!$request->isMethod('GET')) {
            return $response;
        }

        // Determine TTL based on priority:
        // 1. Route parameter override
        // 2. Route-specific configuration
        // 3. Default TTL (from env or fallback)
        $cacheDuration = $ttl ?? $this->getRouteTtl($request) ?? $this->defaultTtl;

        if ($response instanceof Response) {
            $response->headers->add([
                'Cache-Control' => 'public, max-age=' . $cacheDuration,
                'Expires' => gmdate('D, d M Y H:i:s', time() + $cacheDuration) . ' GMT',
                'Vary' => 'Accept-Encoding',
                'ETag' => '"' . md5($response->getContent()) . '"'
            ]);
        }

        return $response;
    }

    /**
     * Get route-specific TTL if configured
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int|null
     */
    protected function getRouteTtl(Request $request): ?int
    {
        $path = trim($request->path(), '/');
        return $this->routeTtl[$path] ?? null;
    }
} 