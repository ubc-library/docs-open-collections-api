<?php
    $apiURL = 'https://oc-index.library.ubc.ca/';
    // Replace this with your API Key
    $apiKey = 'ac40e6c2cb345593ed1691e0a8b601bba398e42d85f81f893c5ab709cec63c6c';

    // Collection to get all items from
    $collection = 'darwin';
    $perPage = 25;
    $offset = 0;

    // First query the API for the count of items in the collection
    $curlOutput = curlGet($apiURL . 'collections/' . $collection . '?api_key=' . $apiKey);
    $itemCount = $curlOutput->data->items;

    // Now we can work out how many pages to loop through
    $pages = (int)ceil($itemCount / $perPage);

    $itemIds = [];
    // First we need to get all the Item Ids from the API
    while ($pages > 0) {
        $curlOutput = curlGet('https://oc-index.library.ubc.ca/collections/' . $collection . '/items?limit=' . $perPage . '&offset=' . $offset . '&api_key=' . $apiKey);

        // Now we want to store them in an array we can use
        foreach ($curlOutput->data as $itemInCollection) {
            $itemIds[] = $itemInCollection->_id;
        }
        $offset += $perPage;
        $pages--;
    }

    $items = [];
    // Next we want to loop through each item id and store the item data.
    foreach ($itemIds as $itemId) {
        $curlOutput = curlGet('https://oc-index.library.ubc.ca/collections/' . $collection . '/items/' . $itemId . '?api_key=' . $apiKey);
        $items[] = $curlOutput->data;
    }

    // We now have our items and can use or manipulate them as needed.
    echo json_encode($items);
    exit;

    //Simple curl function to keep code DRY, will exit on error.
    function curlGet($url)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $curlOutput = json_decode(curl_exec($ch));
            curl_close($ch);
        } catch (Exception $e) {
            var_dump($e);
            exit;
        }
        return $curlOutput;
    }