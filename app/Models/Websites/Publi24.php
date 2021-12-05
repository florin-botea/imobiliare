<?php

namespace App\Models\Websites;

use App\Models\Crawler;
use App\Models\Mapquest;
use App\Models\Marker;
use IvoPetkov\HTML5DOMDocument;

class Publi24 extends Crawler
{
    protected $endpoint = 'https://www.publi24.ro/anunturi/imobiliare/de-inchiriat/apartamente/';
    protected $provider = 'publi24.ro';

    protected function getMarkersFromHtmlString(string $page): array
    {
        $hrefs = [];
        if (!$page) {
            return $hrefs;
        }
        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $a = $dom->querySelectorAll('a.listing-image');
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

        preg_match('/var lat =\s*(\d\d\.\d+)/', $page, $m);
        preg_match('/var lng =\s*(\d\d\.\d+)/', $page, $n);
        if (!isset($m[1], $n[1])) {
            return [];
        }

        $data['lat'] = $m[1];
        $data['lon'] = $n[1];

        preg_match('/>(\d+[\.\,]*\d*) m<sup>2/', $page, $m);
        if (isset($m[1])) {
            $data['usable_area'] = intval($m[1]);
        }

        preg_match('/(\d+) camer/', $page, $m);
        if (isset($m[1])) {
            $data['rooms'] = $m[1];
        }

        $data['seller_type'] = strpos($page, 'agent') ? Marker::SELLER_AGENCY : null;

        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
        $priceNode = $dom->querySelectorAll('.errorcolor span');
        if ($node = $priceNode->item(0)) {
            $data['price'] = intval(str_replace('.', '', $node->nodeValue));
        }
        if ($node = $priceNode->item(1)) {
            $data['currency'] = stripos($node->nodeValue, 'eur') !== false ? Marker::CURRENCY_EURO : Marker::CURRENCY_RON;
        }

        $data['text_price'] = $data['price'] . ($data['currency'] === Marker::CURRENCY_EURO ? ' EURO' : ' RON');

        return $data;
    }

    protected function getPageUrl(int $page): string
    {
        if ($page <= 1) {
            return $this->endpoint;
        }

        return $this->endpoint . '?pag=' . $page;
    }
}
