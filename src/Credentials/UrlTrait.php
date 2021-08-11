<?php

namespace Dormilich\HttpOauth\Credentials;

use Psr\Http\Message\UriInterface;

trait UrlTrait
{
    /**
     * Return URI stripped off credentials and the query string.
     *
     * @param UriInterface $uri
     * @return string
     */
    protected function getUrl(UriInterface $uri): string
    {
        $url = $uri->getScheme() . '://' . $uri->getHost();
        if ($port = $uri->getPort()) {
            $url .= ':' . $port;
        }
        $url .= $uri->getPath();

        return $url;
    }
}
