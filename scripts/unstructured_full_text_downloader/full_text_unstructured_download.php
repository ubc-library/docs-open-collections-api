<?php
    ###################################################################################
    # Application   Open Collections Unstructured Full Text Downloader
    # Organization  The University of British Columbia
    # Author        Stefan Khan-Kernahan
    # Modifier      Sean McNamara
    # Project       UBC Library Open Collections
    #
    # Purpose       The following script allows you to download unstructured / flat full text from Open Collections
    #
    # Usage Example ./downloader --cid tokugawa
    #
    ###################################################################################


    // Replace this with your API Key
    $apiKey = '';
    // Replace this with where you want the downloads to go
    $dir = '/tmp/downloads';

    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', '/tmp/oc-downloader.log');

    function displayHelpMessage()
    {

        echo "_________________________________________________________________\n";
        echo "              OC DOWNLOADER HELP\n";
        echo "\n";
        echo "  --cid           collection to ingest from\n";
        echo "  --help          show this message\n";
        exit;
    }

    $flags = [
        '--cid' => FALSE, // collection to ingest from
    ];

    array_shift($argv);
    while ($arg = array_shift($argv)) {
        switch ($arg) {
            case '--help':
                displayHelpMessage();
                exit;
            case '--cid':
                $flags[$arg] = array_shift($argv);
                break;
            default:
                echo "Found [{$arg}] - no processing command is stated for this argument";
        }
    }

    if (!$flags['--cid']) {
        echo " Error: You must specify a collection [--cid] to process, see --help for more\n";
        exit;
    }

    $ocREST = 'https://oc-index.library.ubc.ca';

    // Create directory if doesn't exist
    if (!is_dir($dir)) {
        mkdir($dir, 0777, TRUE);
    }

    // Get list of all collections if --cid all has been passed
    if ('_all' === "{$flags['--cid']}") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, "{$ocREST}/collections?api_key={$apiKey}");
        $response = curl_exec($ch);
        curl_close($ch);
        $collections = json_decode($response, TRUE)['data'];
    } else {
        $collections [] = "{$flags['--cid']}";
    }

    // Process each collection
    foreach ($collections as $idx) {

    // Create a directory for each collection
        $dlddir = $dir . "/{$idx}";
        if (!is_dir($dlddir)) {
            mkdir($dlddir, 0777, TRUE);
        }

        echo "Generating Download Files: {$idx}\n";

        $finishedProcessing = FALSE;

        $limit = 100;
        $offset = 0;

        while (!$finishedProcessing) {
            echo("GET:/collections/{$idx}/items?api_key={$apiKey}&limit={$limit}&offset={$offset}\n");
            // Get items from collection
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, "{$ocREST}/collections/{$idx}/items?api_key={$apiKey}&limit={$limit}&offset={$offset}");
            $response = curl_exec($ch);
            curl_close($ch);

            $items = json_decode($response, TRUE);

            $items = $items['data'];

            if (empty($items)) {
                $finishedProcessing = TRUE;
                continue;
            } else {
                foreach ($items as $item) {
                    $iid = $item ['_id'];

                    echo(" - GET:/collections/{$idx}/items/{$iid}/output-format/json\n");

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_URL, "{$ocREST}/collections/{$idx}/items/{$iid}/output-format/json?api_key={$apiKey}");
                    $response = curl_exec($ch);
                    curl_close($ch);


                    $d = json_decode($response, TRUE);
                    if (array_key_exists('FullText', $d)) {
                        $d = $d['FullText'][0]['value'];
                        file_put_contents("{$dlddir}/{$iid}_FullText.txt", $d);
                    } else {
                        echo "No full text";
                    }

                }
            }

            $offset += 100;
        }

        echo "Finished Generating Download Files: {$idx}\n";
    }
    echo "Finished Generating Download Files. Goodbye!\n";

    exit;
