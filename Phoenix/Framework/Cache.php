<?php

Cache::SetCache(new Cache());

class Cache
{
    /**
     * @var Cache
     */
    private static $_cache;

    public static function SetCache($cache)
    {
        Cache::$_cache = $cache;
    }

    public static function IsViewCached($route, $smarty, $view)
    {
        Cache::$_cache->IsCached($route, $smarty, $view);
    }

    public static function ClearCachedView($route, $smarty, $view)
    {
        Cache::$_cache->Clear($route, $smarty, $view);
    }

    public static function FetchView($route, $smarty, $view)
    {
        return Cache::$_cache->Fetch($route, $smarty, $view);
    }


    // Cache class functions
    public function Clear($route, $smarty, $view) {}
    public function IsCached($route, $smarty, $view) { return false; }
    public function Fetch($route, $smarty, $view) { return $smarty->fetch($view); }
}

class SmartyCache
{
    protected $timeout;

    function __construct($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param Smarty $smarty
     */
    public function IsCached($route, $smarty, $view)
    {
        $befc = $smarty->caching;
        $befl = $smarty->cache_lifetime;

        $smarty->caching = true;
        $smarty->cache_lifetime = $this->timeout;
        $cached = $smarty->isCached($view, $route);

        $smarty->caching = $befc;
        $smarty->cache_lifetime = $befl;

        return $cached;
    }

    /**
     * @param Smarty $smarty
     */
    public function Clear($route, $smarty, $view)
    {
        $smarty->clearCache($view, $route);
    }

    /**
     * @param Smarty $smarty
     */
    public function Fetch($route, $smarty, $view)
    {
        $befc = $smarty->caching;
        $befl = $smarty->cache_lifetime;

        $smarty->caching = true;
        $smarty->cache_lifetime = $this->timeout;
        $fetch = $smarty->fetch($view, $route);

        $smarty->caching = $befc;
        $smarty->cache_lifetime = $befl;

        return $fetch;
    }
}
