<?php
$original = __DIR__ . '/u611a_10_normalized_geocoded.csv';
$output = __DIR__ . '/u611a_10_normalized_geocoded_destinations.csv';

$originalFP = fopen($original, 'r');
$outputFP = fopen($output, 'w');

$lineNames = fgetcsv($originalFP, 0, '@');
fwrite($outputFP, implode('@', $lineNames) . "@place@region@country\n");
$cache = [];
while ($originalLine = fgetcsv($originalFP, 0, '@')) {
    if (count($originalLine)+2 == count($lineNames)) {
        $originalLine[] = 0;
        $originalLine[] = 0;
    }
    $namedOriginalLine = array_combine($lineNames, $originalLine);
    $nref = $namedOriginalLine['NREF'];

    $place = '';
    $region = '';
    $country = '';
    if (array_key_exists($nref, $cache)) {
        $place = $namedOriginalLine['place'];
        $region = $namedOriginalLine['region'];
        $country = $namedOriginalLine['country'];
    } else {
        $url = "http://xxx/api/accommodations?properties=id($nref)";
        $response = file_get_contents($url);
        $json = json_decode($response);
        print_r($json);
        if ($json->count) {
            $place = trim($json->accommodations[0]->destination->parent->parent->name);
            $region = trim($json->accommodations[0]->destination->parent->name);
            $country = trim($json->accommodations[0]->destination->name);

            $cache[$nref] = [
                'place' => $namedOriginalLine['place'],
                'region' => $namedOriginalLine['region'],
                'country' => $namedOriginalLine['country'],
            ];
        }
    }
    $namedOriginalLine['place'] = $place;
    $namedOriginalLine['region'] = $region;
    $namedOriginalLine['country'] = $country;

    $outputLine = implode('@', $namedOriginalLine) . "\n";
    fwrite($outputFP, $outputLine);
}

$destinations = __DIR__ . '/destinations.csv';
$destinationsFP = fopen($destinations, 'w');
foreach ($cache as $item) {
    fwrite($destinationsFP, implode(';', $item) . "\n");
}