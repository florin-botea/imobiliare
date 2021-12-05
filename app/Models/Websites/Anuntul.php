<?php

namespace App\Models\Websites;

use App\Models\Crawler;
use App\Models\Mapquest;
use App\Models\Marker;
use IvoPetkov\HTML5DOMDocument;

class Anuntul extends Crawler
{
    protected $endpoint = 'https://www.anuntul.ro/anunturi-imobiliare-inchirieri/';
    protected $provider = 'anuntul.ro';

    protected function getMarkersFromHtmlString(string $page): array
    {
        $hrefs = [];
        if (!$page) {
            return $hrefs;
        }
        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $a = $dom->querySelectorAll('.title-anunt a');
        foreach ($a as $_a) {
            $hrefs[] = $_a->getAttribute('href');
        }

        return $hrefs;
    }

    protected function parseMarkerFromHtmlString(string $page): array
    {
        if (!$page) {
            return [];
        }

        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $addressNode = $dom->querySelector('.descriere-anunt strong');
        if (!$addressNode) {
            return [];
        }
        $lat = $dom->querySelector('#lat');
        $lng = $dom->querySelector('#lng');
        if (!$lat || !$lng) {
            return [];
        }
        $lat = $lat->getAttribute('value');
        $lng = $lng->getAttribute('value');

        $priceNode = $dom->querySelector('.titlu-anunt > .price');
        $detailsNode = $dom->querySelector('.descriere-anunt p');

        $sellerType = null;
        if ($priceNode && $priceNode->nodeValue) {
            $textPrice = $this->getPrice($priceNode);
            $intPrice = intval(str_replace('.', '', $textPrice));
        } else {
            $textPrice = '- Euro';
            $intPrice = 1;
        }

        if ($detailsNode && $detailsNode->nodeValue) {
            $usableArea = $this->getUsableArea($detailsNode->nodeValue);
            $nrRooms = $this->getNrRooms($detailsNode->nodeValue);
        } else {
            $usableArea = null;
            $nrRooms = null;
        }

        $data['lat'] = $lat;
        $data['lon'] = $lng;
        $data['text_price'] = $textPrice;
        $data['price'] = $intPrice;
        $data['type'] = Marker::TYPE_RENT;
        $data['currency'] = stripos($textPrice, 'EUR') !== false ? Marker::CURRENCY_EURO : Marker::CURRENCY_RON;
        $data['usable_area'] = $usableArea;
        $data['rooms'] = $nrRooms;
        $data['seller_type'] = $sellerType;

        return $data;
    }

    private function getPrice($node)
    {
        return str_replace([
            'html5-dom-document-internal-entity1-euro-end',
            'html5-dom-document-internal-entity2-259-end',
        ],
        [
            'EURO',
            'a',
        ], $node->nodeValue);
    }

    private function getUsableArea(string $page)
    {
        preg_match("/(\d+) mp/", $page, $m);

        return isset($m[1]) ? $m[1] : null;
    }

    public function getNrRooms(string $page)
    {
        preg_match("/(\d+) camer/", $page, $m);

        return isset($m[1]) ? $m[1] : null;
    }

    protected function getPageUrl(int $page): string
    {
        if ($page <= 1) {
            return $this->endpoint;
        }

        return $this->endpoint . '?page=' . $page;
    }
}
