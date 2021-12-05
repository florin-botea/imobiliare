<?php

namespace App\Models\Websites;

use App\Models\Crawler;
use App\Models\Mapquest;
use App\Models\Marker;
use IvoPetkov\HTML5DOMDocument;

class Storia extends Crawler
{
    protected $endpoint = 'https://www.storia.ro/inchiriere/apartament/bucuresti/';
    protected $provider = 'storia.ro';

    protected function getMarkersFromHtmlString(string $page): array
    {
        $hrefs = [];
        if (!$page) {
            return $hrefs;
        }
        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $a = $dom->querySelectorAll('.offer-item-details header a');
        foreach ($a as $_a) {//dd($a);
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

        preg_match('/"lat"\:(\d\d\.\d+)\,"long"\:(\d\d\.\d+)/', $page, $m);
        if (empty($m[1])) {
            return [];
        }
        $data['lat'] = $m[1];
        $data['lon'] = $m[2];

        preg_match('/"price_currency"\:"(\w+)"/', $page, $m);
        if (isset($m[1]) && stripos($m[1], 'eur') === false) {
            $data['currency'] = Marker::CURRENCY_RON;
        }

        preg_match('/"Price"\:(\d+)/', $page, $m);
        if (isset($m[1])) {
            $data['price'] = $m[1];
        }

        preg_match('/"Net_area"\:"(\d+)/', $page, $m);
        if (isset($m[1])) {
            $data['usable_area'] = $m[1];
        }

        preg_match('/"poster_type":"(\w+)"/', $page, $m);
        if (isset($m[1])) {
            $data['saller_type'] = $m[1] === 'agency' ? Marker::SELLER_AGENCY : Marker::SELLER_OWNER;
        }

        preg_match('/"key"\:"rooms_num"\,"value"\:"(\d+)",/', $page, $m);
        if (isset($m[1])) {
            $data['rooms'] = $m[1];
        }

        $data['text_price'] = $data['price'] . ($data['currency'] === Marker::CURRENCY_EURO ? ' EURO' : ' RON');

        // preg_match('/"Floor_no"\:\["fl_(\d+)"/', $page, $m);
        // if (isset($m[1])) {
        //     $data['usable_area'] = $m[1];
        // }

        return $data;
    }

    protected function getPageUrl(int $page): string
    {
        if ($page <= 1) {
            return $this->endpoint;
        }

        return $this->endpoint . '?page=' . $page;
    }
}
