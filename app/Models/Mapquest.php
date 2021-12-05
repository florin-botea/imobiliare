<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapquest extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'lat',
        'lon'
    ];

    private static $endpoint = 'http://open.mapquestapi.com/geocoding/v1/address';
    private static $apiKey = 'HDq8KUa2sMDmm9BB2NG18l7UX3EHaoQh';

    public static function search($q)
    {
        $result = self::where('query', trim($q))->first();
        if ($result) {
            return [
                'lat' => $result->lat,
                'lon' => $result->lon
            ];
        }
// $q = "RO, Municipiul București,Soseaua Alexandriei, spatiu comercial stradal";
// echo $q.'<br>';
        $url = self::$endpoint . '?key=' . self::$apiKey;
        $data = http_build_query([
            'key' => self::$apiKey,
            'location' => $q,

        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = (array) json_decode($result, true);//vd($result);
        if (!isset($result['results'][0]['locations'][0]['latLng'])) {
            return null;
        }
        $result = $result['results'][0]['locations'][0]['latLng'];

        self::create([
            'query' => $q,
            'lat' => $result['lat'],
            'lon' => $result['lng'],
        ]);

        return [
            'lat' => $result['lat'],
            'lon' => $result['lng'],
        ];
    }
}
