<?php
//PlagScan API usage example in PHP (requires PHP 5 or higher)
//Make sure to change user and key and PID!

// Submit those variables to the server

//"apitest",
//"W9fUaSq2vjkXUPMmenlmp3Xqnc1xWPtN",
//""+6323,
$post_data = array(
    	"USER"=> $apiUser,
    	"KEY"=> $apiKey,
    	"VERSION"=> "2.1",
    	"METHOD"=> "retrieve",
    	"PID"=> ""+intval($_SERVER["QUERY_STRING"]),
    	"MODE"=> "9"
);

function post_request($url, $data, $referer='') {

    // Convert the data array
    $data = http_build_query($data); //for some PHP versions use: http_build_query($data, '', '&');

    // parse the given URL
    $url = parse_url($url);

    // extract host and path:
    $host = $url['host'];
    $path = $url['path'];

    // open a socket connection on port 443 - timeout: 30 sec
    $fp = fsockopen("ssl://".$host, 443, $errno, $errstr, 30);

    if ($fp){

        // send the request headers:
        fputs($fp, "POST $path HTTP/1.0\r\n");
        fputs($fp, "Host: $host\r\n");

        if ($referer != '')
            fputs($fp, "Referer: $referer\r\n");

        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 1024);
        }
    }
    else {
        return array(
            'status' => 'err',
            'error' => "$errstr ($errno)"
        );
    }

    // close the socket connection:
    fclose($fp);

    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);

    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    // return as structured array:
    return array(
        'status' => 'ok',
        'header' => $header,
        'content' => $content
    );
}

// Send the request
$result = post_request('https://www.plagscan.com/api/', $post_data);

if ($result['status'] == 'ok'){

    // Print headers
    //echo $result['header'];

     //if docx data successfully retrieved try to write this to a file:
    //if((intval($post_data["MODE"])==3)&&(!strpos($result['content'],"N/A")))
    //	if(!file_put_contents("test.docx",$result['content']))
    //		echo '<br/><b>Could not write test.docx - no access rights?</b>';

    //echo '<hr />';

    // print the result of the whole request:
    //echo "<pre>".str_replace("<","&lt;",$result['content'])."</pre>";
    //For live use you need to parse the xml in $result['content']!
    header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    if(strstr($_SERVER["HTTP_USER_AGENT"],"MSIE")==false) {
		header("X-Download-Options: noopen "); // For IE8
		header("X-Content-Type-Options: nosniff"); // For IE8
	}
    header('Content-type: application/pdf');
    header("Content-Transfer-Encoding: binary");
	header('Content-Disposition: attachment; filename="PSreport_'.intval($_SERVER["QUERY_STRING"]).'.pdf"');

    echo $result['content'];

}
else {
    echo 'An error occured: ' . $result['error'];
}

?>