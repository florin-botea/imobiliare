<?php

namespace App\Models\Websites;

use App\Models\Crawler;
use App\Models\Mapquest;
use App\Models\Marker;
use IvoPetkov\HTML5DOMDocument;

class Lajumate extends Crawler
{
    protected $endpoint = 'https://lajumate.ro/anunturi_apartamente-de-inchiriat_in-bucuresti-if.html';
    protected $provider = 'lajumate.ro';

    protected function getMarkersFromHtmlString(string $page): array
    {
        $hrefs = [];
        if (!$page) {
            return $hrefs;
        }
        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $a = $dom->querySelectorAll('a.main_items.item_cart');
        foreach ($a as $_a) {
            $href = $_a->getAttribute('href');
            if ($href === '#') {
                continue;
            }
            $hrefs[] = $_a->getAttribute('href');
        }

        $hrefs = array_unique(array_filter($hrefs));

        return $hrefs;
    }

    protected function parseMarkerFromHtmlString(string $page): array
    {
        if (!$page) {
            return [];
        }

        $data = [
            'currency' => Marker::CURRENCY_EURO,
            'text_price' => null,
            'price' => 1,
            'type' => Marker::TYPE_RENT,
            'usable_area' => null,
            'rooms' => null,
            'seller_type' => null,
        ];

        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $lon = $dom->querySelector('#lon_value');
        $lat = $dom->querySelector('#lat_value');
        if (!$lon || !$lat) {
            return [];
        }

        $data['lon'] = $lon->getAttribute('value');
        $data['lat'] = $lat->getAttribute('value');

        $priceNode = $dom->querySelector('#price');
        if ($priceNode) {
            $data['price'] = intval(str_replace('.', '', $priceNode->nodeValue));
            $data['currency'] = stripos($priceNode->nodeValue, 'eur') !== false ? Marker::CURRENCY_EURO : Marker::CURRENCY_RON;
        }

        preg_match('/(\d+[\.\,]*\d*)\s*mp/', $page, $m);
        if (isset($m[1])) {
            $data['usable_area'] = intval($m[1]);
        }

        preg_match('/(\d+) camer/', $page, $m);
        if (isset($m[1])) {
            $data['rooms'] = $m[1];
        }

        $data['text_price'] = $data['price'] . ($data['currency'] === Marker::CURRENCY_EURO ? ' EURO' : ' RON');

        return $data;
    }

    protected function getPageUrl(int $page): string
    {
        if ($page <= 1) {
            return $this->endpoint;
        }

        return str_replace('.html', "_$page.html", $this->endpoint);
    }
}
