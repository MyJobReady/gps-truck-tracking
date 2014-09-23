<?php

    function post_curl($url, $post_data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json', 
            'Accept-Encoding: gzip,deflate', 
            'Content-Type: application/json', 
            'Content-Length: ' . strlen($post_data)
        ));

        $result = curl_exec($ch);
       
        return $result;
    }
?>