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
            return false;
        }

        foreach (array_keys(app('clumsy.assets')->sets()) as $set) {
            app('events')->listen($this->getEvent($set), function () use ($set) {
                return app('clumsy.assets')->dump($set);
            }, 25);
        }

        $header_content = array_flatten([
            event('Print styles'),
            event('Print objects'),
            event('Print scripts'),
        ]);
        $header_content = implode(PHP_EOL, array_filter($header_content));

        $pos = strripos($content, '</head>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $header_content . substr($content, $pos);
        }

        $footer_content = event('Print footer scripts');
        $footer_content = implode(PHP_EOL, array_filter($footer_content));

        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $footer_content . substr($content, $pos);
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
