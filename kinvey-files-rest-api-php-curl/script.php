<?php
    // TODO: Set the file's location.
    // Test with the sample files from the folder.
    $fileLocation = "./2.jpeg";
    // Try to fetch the file and its metadata.
    $actualFile = file_get_contents($fileLocation);
    $fileContentType = mime_content_type($fileLocation);
    $fileSize = filesize($fileLocation);
    // Check if all is fine with the file.
    if ($actualFile === false || $fileContentType === false || $fileSize === false) {
        echo "Problems with the file. Aborting. \n";
        die();
    }

    // File found. Continue with the request to Kinvey backend.
    // TODO: Change your Kinvey ID (appKey)!
    // TODO: Change your "Authorization" header!
    $kinveyCurl = curl_init();
    curl_setopt_array($kinveyCurl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => "https://baas.kinvey.com/blob/kid_xxx/?tls=true",
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode(array(
            "mimeType" => $fileContentType,
            "_public" => false,
            "_acl" => [
                "gr" => true
            ],
            "size" => $fileSize
        )),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Authorization: Kinvey xxx",
            "X-Kinvey-API-Version: 3",
            "X-Kinvey-Content-Type: " . $fileContentType
        )
    ));
    // Send the request and save response to $kinveyResponse.
    $kinveyResponse = curl_exec($kinveyCurl);
    // Close request to clear up resources.
    curl_close($kinveyCurl);
    $kinveyResponse = json_decode($kinveyResponse);
    
    // Proceed further with checking if there's a URL for storage upload.
    if (!isset($kinveyResponse->_uploadURL)) {
        echo "Upload URL did not arrive with the response from Kinvey. Aborting second request. \n";
        var_dump($kinveyResponse);
        die();
    }
    echo "Start the uploading to the storage... \n";
    $uploadURL = $kinveyResponse->_uploadURL;
    $storageCurl = curl_init();
    curl_setopt_array($storageCurl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $uploadURL,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $actualFile,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: " . $fileContentType
        )
    ));
    $storageResponse = curl_exec($storageCurl);
    $storageResponseStatusCode = curl_getinfo($storageCurl)["http_code"];
    curl_close($storageCurl);
    // The success status code is 200. Might change!
    if ($storageResponseStatusCode !== 200) {
        echo "Something went wrong with uploading to the storage. \n";
        var_dump($storageResponse);
        die();
    }
    echo "Status Code from storage " . $storageResponseStatusCode . "\n";
?>
