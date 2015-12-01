<?php

namespace Clumsy\Assets\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Clumsy\Assets\Facade as Asset;

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

        foreach (array_keys(Asset::sets()) as $set) {
            Event::listen($this->getEvent($set), function () use ($set) {
                return Asset::dump($set);
            }, 25);
        }

        $header_content = array_flatten([
            Event::fire('Print styles'),
            Event::fire('Print objects'),
            Event::fire('Print scripts'),
        ]);
        $header_content = implode(PHP_EOL, array_filter($header_content));

        $pos = strripos($content, '</head>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $header_content . substr($content, $pos);
        }

        $footer_content = Event::fire('Print footer scripts');
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
