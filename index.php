<?php
$original = __DIR__ . '/u611a_10_normalized_geocoded.csv';
$output = __DIR__ . '/u611a_10_normalized_geocoded_destinations.csv';

$originalFP = fopen($original, 'r');
$outputFP = fopen($output, 'w');

$lineNames = fgetcsv($originalFP, 0, '@');
fwrite($outputFP, implode('@', $lineNames) . "@place@region@country\n");
$cache = [];
$i = 0;
while ($originalLine = fgetcsv($originalFP, 0, '@')) {
    // Sleep for 10 seconds after 100 requests.
    if ($i != 0 && $i % 100 == 0) {
        sleep(10);
    }
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
        if ($cache[$nref] !== false) {
            $place = $cache[$nref]['place'];
            $region = $cache[$nref]['region'];
            $country = $cache[$nref]['country'];
        }
    } else {
        $i++;
        echo $i;
        $url = "http://search.interhome.com/api/accommodations?properties=id($nref)";
        $response = file_get_contents($url);
        $json = json_decode($response);
        //print_r($json);
        if (isset($json->count)) {
            $place = trim($json->accommodations[0]->destination->parent->parent->name);
            $region = trim($json->accommodations[0]->destination->parent->name);
            $country = trim($json->accommodations[0]->destination->name);

            $cache[$nref] = [
                'place' => $place,
                'region' => $region,
                'country' => $country,
            ];
        } else {
            $cache[$nref] = false;
        }
    }
    $namedOriginalLine['place'] = $place;
    $namedOriginalLine['region'] = $region;
    $namedOriginalLine['country'] = $country;

    $outputLine = implode('@', $namedOriginalLine) . "\n";
    fwrite($outputFP, $outputLine);
}

// Store destinations into a file.
$destinations = __DIR__ . '/destinations.csv';
$destinationsFP = fopen($destinations, 'w');
$cache2 = [];
foreach ($cache as $item) {
    // Prevent empty lines.
    if ($item !== false) {
        $cacheKey = implode("", $item);
        // Prevent duplicates.
        if (!key_exists($cacheKey, $cache2)) {
            fwrite($destinationsFP, implode(';', $item) . "\n");
            $cache2[$cacheKey] = true;
        }
    }
}