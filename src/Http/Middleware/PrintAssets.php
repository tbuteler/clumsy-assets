<?php

namespace Clumsy\Assets\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrintAssets
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        return $this->triggerEvents($response);
    }

    public function triggerEvents(Response $response)
    {
        $content = $response->getContent();

        if (!$content) {
            return $response;
        }

        foreach (array_keys(app('clumsy.assets')->sets()) as $set) {
            app('events')->listen($this->getEvent($set), function () use ($set) {
                return app('clumsy.assets')->dump($set);
            }, 25);
        }

        $headerContent = array_flatten([
            event('Print styles'),
            event('Print objects'),
            event('Print scripts'),
        ]);
        $headerContent = implode(PHP_EOL, array_filter($headerContent));

        $pos = strripos($content, '</head>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $headerContent . substr($content, $pos);
        }

        $footerContent = event('Print footer scripts');
        $footerContent = implode(PHP_EOL, array_filter($footerContent));

        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $footerContent . substr($content, $pos);
        }

        $response->setContent($content);

        return $response;
    }

    protected function getEvent($set)
    {
        switch ($set) {
            case 'styles':
                return 'Print styles';

            case 'header':
                return 'Print scripts';

            case 'footer':
                return 'Print footer scripts';

            default:
                return 'Print objects';
        }
    }
}
