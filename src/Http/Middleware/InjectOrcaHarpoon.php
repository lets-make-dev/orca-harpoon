<?php

namespace MakeDev\OrcaHarpoon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\HttpFoundation\Response;

class InjectOrcaHarpoon
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false) {
            return $response;
        }

        $sourceModule = $this->resolveSourceModule($request);

        $livewireTag = Blade::render(
            "@livewire('orcaharpoon-harpoon', ['sourceModule' => \$sourceModule])",
            ['sourceModule' => $sourceModule]
        );

        $content = str_replace('</body>', $livewireTag."\n</body>", $content);

        $response->setContent($content);

        return $response;
    }

    private function shouldInject(Request $request, Response $response): bool
    {
        if (! app()->isLocal()) {
            return false;
        }

        if (! config('orcaharpoon.enabled', true)) {
            return false;
        }

        if ($request->hasHeader('X-Livewire')) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html');
    }

    private function resolveSourceModule(Request $request): string
    {
        $route = $request->route();
        if (! $route) {
            return '';
        }

        $uses = $route->getAction('uses');

        if (is_string($uses) && str_starts_with($uses, 'Modules\\')) {
            return explode('\\', $uses)[1] ?? '';
        }

        return '';
    }
}
