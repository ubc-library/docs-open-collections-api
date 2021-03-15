<?php
    // Replace this with your API Key
    $apiKey = '';

    // Collection we want to harvest
    $collection = 'darwin';
    // Setup variables
    $limit = 100;
    $itemIds = [];
    $items = [];

    // First we need to find out how many items are in the collection
    $curlOutput = curlGet('https://oc-index.library.ubc.ca/collections/' . $collection.'?api_key='.$apiKey);

    // Now we have the item count, figure out the page count and create an offset of 0
    $itemCount = $curlOutput->data->items;
    $pageCount = ceil($itemCount / $limit);
    $offset = 0;

    // Loop through the pages and extract the item ids into the $itemIds array.
    while ($pageCount > 0) {
        $curlOutput = curlGet('https://oc-index.library.ubc.ca/collections/' . $collection . '/items?api_key='.$apiKey.'&offset=' . $offset . '&limit=' . $limit);
        foreach ($curlOutput->data as $item) {
            $itemIds[] = $item->_id;
        }
        $pageCount--;
        $offset = $offset + 100;
    }

    // Loop through the item ids and extract metadata into the $items array.
    foreach ($itemIds as $itemId) {
        $curlOutput = curlGet('https://oc-index.library.ubc.ca/collections/' . $collection . '/items/' . $itemId.'?api_key='.$apiKey);
        $item = $curlOutput->data;
        $items[] = array(
            "id"          => $itemId,
            "title"       => $item->Title[0]->value,
            "description" => $item->Description[0]->value,
            "fullText"    => property_exists($item, 'FullText') ? $item->FullText[0]->value : null
        );
    }

    // We now have the items stored in $items, uncomment below to check it out.
    // echo json_encode($items);
    // exit;

    // For more fun lets add them into a CSV file. ( You could have file permission problems attempting this )
    $fp = fopen($collection . '.csv', 'w');
    fputcsv($fp, ['ID', 'TITLE', 'DESCRIPTION', 'FULLTEXT'], '~', '"');
    foreach ($items as $item) {
        fputcsv($fp, $item, '~', '"');
    }
    fclose($fp);

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
