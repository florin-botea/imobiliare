<?php

namespace App\Models\Websites;

use App\Models\Crawler;
use App\Models\Marker;
use IvoPetkov\HTML5DOMDocument;

class Imobiliare extends Crawler
{
    protected $endpoint = 'https://www.imobiliare.ro/inchirieri-apartamente/bucuresti';
    protected $provider = 'imobiliare.ro';

    protected function getMarkersFromHtmlString(string $page): array
    {
        $hrefs = [];
        if (!$page) {
            return $hrefs;
        }
        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $a = $dom->querySelectorAll('.titlu-anunt a');
        foreach ($a as $_a) {
            $hrefs[] = $_a->getAttribute('href');
        }
        $hrefs = array_filter($hrefs, function($url) {
            return strpos($url, 'inchirieri-apartamente');
        });

        return $hrefs;
    }

    protected function parseMarkerFromHtmlString(string $page): array
    {
        if (!$page) {
            return [];
        }

        $latLon = $this->getCoord($page);
        if (!$latLon) {
            return [];
        }

        $dom = new HTML5DOMDocument;
        $dom->loadHtml($page, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);

        $textPrice = $dom->querySelector('.pret');
        $textPrice = $this->getPrice($textPrice);
        $textPrice = trim(preg_replace('/\s+/', ' ', $textPrice));
        $textPrice = str_ireplace('EUR ', 'EURO', $textPrice);
        $textPrice = trim(explode('/', $textPrice)[0]);

        if ($pageData = $this->getPageData($page)) {
            $usableArea = $pageData['propertySurface'];
            $intPrice = $pageData['propertyPrice'];
            $nrRooms = $pageData['propertyNumberOfRooms'];
            $sellerType = $pageData['sSellerType'] === 'agentie' ? Marker::SELLER_AGENCY : Marker::SELLER_OWNER;
        } else {
            $usableArea = $this->getUsableArea($page);
            $intPrice = (int) str_replace('.', '', $textPrice);
            $nrRooms = null;
            $sellerType = null;
        }

        $data = $latLon;
        $data['text_price'] = $textPrice;
        $data['price'] = $intPrice;
        $data['type'] = Marker::TYPE_RENT;
        $data['currency'] = stripos($textPrice, 'EUR') !== false ? Marker::CURRENCY_EURO : Marker::CURRENCY_RON;
        $data['usable_area'] = $usableArea;
        $data['rooms'] = $nrRooms;
        $data['seller_type'] = $sellerType;

        return $data;
    }

    private function getCoord(string $page)
    {
        preg_match('/lat\/([0-9\.]+)\/lon\/([0-9\.]+)/', $page, $m);
        if (count($m) < 3) {
            return [];
        }
        return [
            'lat' => (float) $m[1],
            'lon' => (float) $m[2],
        ];
    }

    private function getPrice($price)
    {
        $string = [];
        foreach ($price->childNodes as $node) {//dd($node);
            if ($node->nodeName === '#text') {
                $string[] = $node->nodeValue;
            }
            elseif (strpos($node->getAttribute('class'), 'tva') !== false) {
                $string[] = str_replace([
                    'html5-dom-document-internal-entity1-euro-end',
                    'html5-dom-document-internal-entity2-259-end',
                ],
                [
                    'EURO',
                    'a',
                ], $node->nodeValue);
            }
        }

        return implode(' ', $string);
    }

    private function getUsableArea(string $page)
    {
        preg_match("/'propertySurface': '(\d+)'/", $page, $m);

        return isset($m[1]) ? $m[1] : null;
    }

    private function getPageData(string $page)
    {
        preg_match('/var dataLayer = \[(((?!\];).)*)\];/s', $page, $m);

        if (isset($m[1])) {
            $var = preg_replace('/\s+/', '', $m[1]);
            $var = str_replace(["'", "\n", ',}', 'preferinte_cookie'], ['"', '', '}', '1'], $var);
            return (array) json_decode($m[1], true);
        }
        return [];
    }

    protected function getPageUrl(int $page): string
    {
        if ($page <= 1) {
            return $this->endpoint;
        }

        return $this->endpoint . '?pagina=' . $page;
    }
}
