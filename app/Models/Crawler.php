<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\File;

abstract class Crawler
{

    protected $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
    protected $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
    ];

    protected function get($url, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $dom = curl_exec($ch);
        curl_close($ch);
        return $dom;
    }

    protected function multiGet(array $url, $headers = [])
    {
        $results = [];

        if (!$url) {
            return [];
        }

        if (!file_exists(storage_path('app/cookie.txt'))) {
            File::put(storage_path('app/cookie.txt'), '');
        }
        $cookies = tempnam(storage_path('app'),'cookie.txt');

        $mh = curl_multi_init();
        foreach($url as $key => $value){
            $ch[$key] = curl_init($value);
            curl_setopt($ch[$key], CURLOPT_URL, $value);
            curl_setopt($ch[$key], CURLOPT_HEADER, 0);
            curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$key], CURLOPT_COOKIEJAR, $cookies);
            curl_setopt($ch[$key], CURLOPT_COOKIEFILE, $cookies);
            curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch[$key], CURLOPT_VERBOSE, true);
            curl_setopt($ch[$key], CURLOPT_USERAGENT, $this->agent);
            curl_setopt($ch[$key], CURLOPT_HTTPHEADER, $this->headers);

            curl_multi_add_handle($mh,$ch[$key]);
        }

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach($ch as $key => $_ch){
            $results[$key] = curl_multi_getcontent($_ch);
            curl_multi_remove_handle($mh, $_ch);
        }

        curl_multi_close($mh);

        return $results;
    }

    protected function indexUrls(array $urls)
    {
        foreach ($urls as $page => $chunk) {
            Marker::whereIn('url', $chunk)->update(['checked' => 1]);
            MarkerIndexing::whereIn('url', $chunk)->delete();
            // stergerea o va face marker cand ajunge la capat
            foreach ($chunk as $url) {
                MarkerIndexing::create([
                    'provider' => $this->provider,
                    'url' => $url,
                    'page' => $page
                ]);
            }
        }
    }

    public function indexStartingFromPage(int $page, int $limit)
    {
        if ($page <= 1) {
            MarkerIndexing::where('provider', $this->provider)->delete();
            Marker::where('provider', $this->provider)->where('checked', 0)->delete();
            Marker::where('provider', $this->provider)->update(['checked' => 0]);
        }

        $urls = [];
        $pages = [];
        for ($i = $page; $i < $page + $limit; $i++) {
            $pages[] = $i;
            $urls[] = $this->getPageUrl($i);
        }

        $pages = array_combine($pages, $this->multiGet($urls));
        $hrefs = [];
        // eliminam duplicatele si le grupam cu pagina
        foreach ($pages as $i => $page) {
            $hrefs = array_merge($hrefs, array_fill_keys($this->getMarkersFromHtmlString($page), $i));
        }
        $_hrefs = [];
        foreach ($hrefs as $href => $page) {
            $_hrefs[$page][] = $href;
        }

        $this->indexUrls($_hrefs);

        return count($hrefs);
    }

    public function importMarkers(int $limit)
    {
        $hrefs = MarkerIndexing::where('provider', $this->provider)->orderBy('page')->limit($limit)->get()->toArray();
        $ids = array_column($hrefs, 'id');
        $hrefs = array_column($hrefs, 'url');
        $pages = array_combine($hrefs, $this->multiGet($hrefs));
        foreach ($pages as $url => $page) {
            $marker = null;
            try {
                $marker = $this->parseMarkerFromHtmlString($page);
            } catch(Exception $e) {}

            if ($marker) {
                Marker::firstOrCreate([
                    'provider' => $this->provider,
                    'url' => $url
                ], $marker);
            } else {
                vd('Could not parse '. $url);
            }
        }
        MarkerIndexing::whereIn('id', $ids)->delete();
    }

    abstract protected function getPageUrl(int $page): string;
    abstract protected function getMarkersFromHtmlString(string $page): array;
    /**
     * 'price',
     * 'text_price',
     * 'type',
     * 'lat',
     * 'lon',
     * 'type',
     * 'currency',
     * 'usable_area',
     * 'rooms',
     * 'seller_type',
     */
    abstract protected function parseMarkerFromHtmlString(string $page): array;
}
