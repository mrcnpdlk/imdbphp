<?php

namespace Imdb;

use mrcnpdlk\Psr16Cache\Adapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * Handles requesting urls, including the caching layer
 */
class Pages
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var \mrcnpdlk\Psr16Cache\Adapter
     */
    protected $oAdapter;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var array
     */
    protected $pages = [];


    /**
     * @param Config          $config
     * @param CacheInterface  $cache
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, CacheInterface $cache = null, LoggerInterface $logger = null)
    {
        $this->config   = $config;
        $this->cache    = $cache;
        $this->logger   = $logger ?? new NullLogger();
        $this->oAdapter = new Adapter($this->cache, $this->logger);
    }

    /**
     * @param $url
     *
     * @return \Imdb\Request
     */
    protected function buildRequest($url): Request
    {
        return new Request($url, $this->config);
    }

    /**
     * Retrieve the content of the specified $url
     * Caching will be used where possible
     *
     * @param $url
     *
     * @return string
     */
    public function get($url): string
    {
        try {
            if (!empty($this->pages[$url])) {
                return $this->pages[$url];
            }

            $this->pages[$url] = $this->oAdapter->useCache(
                function () use ($url) {
                    return $this->requestPage($url);
                },
                [$this->getCacheKey($url)],
                1
            );

            return $this->pages[$url];
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            return '';
        }

    }

    /**
     * @param $url
     *
     * @return string
     */
    protected function getCacheKey($url): string
    {
        $urlParts = parse_url($url);
        $cacheKey = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');

        return trim($cacheKey, '/');
    }

    /**
     * Request the page from IMDb
     *
     * @param $url
     *
     * @return string Page html. Empty string on failure
     * @throws Exception\Http
     */
    protected function requestPage($url)
    {
        $this->logger->info("[Page] Requesting [$url]");
        $req = $this->buildRequest($url);
        if (!$req->sendRequest()) {
            $this->logger->error("[Page] Failed to connect to server when requesting url [$url]");
            if ($this->config->throwHttpExceptions) {
                throw new Exception\Http("Failed to connect to server when requesting url [$url]");
            }

            return '';
        }

        if (200 == $req->getStatus()) {
            return $req->getResponseBody();
        }

        if ($redirectUrl = $req->getRedirect()) {
            $this->logger->debug("[Page] Following redirect from [$url] to [$redirectUrl]");

            return $this->requestPage($redirectUrl);
        }

        $this->logger->error('[Page] Failed to retrieve url [{url}]. Response headers:{headers}',
            ['url' => $url, 'headers' => $req->getLastResponseHeaders()]);
        if ($this->config->throwHttpExceptions) {
            $exception                 = new Exception\Http("Failed to retrieve url [$url]. Status code [{$req->getStatus()}]");
            $exception->HTTPStatusCode = $req->getStatus();
            throw new $exception;
        }

        return '';
    }

}
