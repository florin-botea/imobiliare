<?php

namespace App\Models\Websites;

use App\Models\Crawler;
use App\Models\Mapquest;
use App\Models\Marker;
use IvoPetkov\HTML5DOMDocument;

class Titirez extends Crawler
{
    protected $endpoint = 'https://www.titirez.ro/inchirieri-apartamente/bucuresti';
    protected $provider = 'titirez.ro';

    protected function getMarkersFromHtmlString(string $page): array
    {
        $hrefs = [];
        if (!$page) {
            return $hrefs;
        }
        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $a = $dom->querySelectorAll('#listingResults .item-anunt a');
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

        $mapNode = $dom->querySelector('#map');
        if (!$mapNode) {
            return [];
        }
        $data['lat'] = $mapNode->getAttribute('data-latitude');
        $data['lon'] = $mapNode->getAttribute('data-longitude');

        $priceNode = $dom->querySelectorAll('.pret-anunt meta');
        if ($node = $priceNode->item(1)) {
            $data['price'] = intval(str_replace('.', '', $node->getAttribute('content')));
        }
        if ($node = $priceNode->item(0)) {
            $data['currency'] = stripos($node->getAttribute('content'), 'eur') !== false ? Marker::CURRENCY_EURO : Marker::CURRENCY_RON;
        }

        preg_match('/(\d+[\.\,]*\d*)\s*m<sup>2/', $page, $m);
        if (isset($m[1])) {
            $data['usable_area'] = intval($m[1]);
        }
        else {
            preg_match('/(\d+[\.\,]*\d*)\s*mp/', $page, $m);
            if (isset($m[1])) {
                $data['usable_area'] = intval($m[1]);
            }
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

        return $this->endpoint . '?p=' . $page;
    }
}
