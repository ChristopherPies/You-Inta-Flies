<?php
/* https://www.plagscan.com/api-guide */
class DDM_PlagScan_21_PlagScan {

    private $apiKey;

    private $user;

    private $version = '2.1';

    private $url = 'https://www.plagscan.com/api/';

    private $docXToDelete = array();

    /**
     *
     */
    public function __construct($user = null, $apiKey = null) {
        $application = new Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/configs/application.ini'
        );
        $config = $application->getOptions();
        if(!empty($config['plagscan'])) {
            $plagscan = $config['plagscan'];

            $this->apiKey = $plagscan['apikey'];
            $this->user = $plagscan['user'];
        }

        if(!empty($user) && !empty($apiKey)) {
            $this->user = $user;
            $this->apiKey = $apiKey;
        }
    }

    /**
     * Retrieve report modes
     *    0      Retrieve statistics only, such as plagiarism level, number of hits and sources
     *    1      Retrieve links; to list of possible plagiarism sources; e.g. http://www.plagscan.com/report?6055 and in-document view of possible plagiarism sources; e.g. http://www.plagscan.com/view?6055
     *    2      Retrieve XML with data on all possible plagiarism sources
     *    3      Retrieve annotated Docx document (if available, depending on user configuration)
     *    4      Retrieve HTML document with annotations
     *    5      Retrieve HTML report of matches sorted by relevance
     *    6      Another plagscan report
     *
     * @param string $plagScanId
     * @param int $mode
     * @return mixed  if the return is xml we will try to return a SimpleXml objecct - or the xml if it's invalid.
     *                If it's a docx we will return the file path to the docx which will be deleted after the destructor runs
     *                If it's html we just return it
     * @throws Exception
     */
    public function retrieveArticleStats($plagScanId, $mode = 0) {
        // Submit those variables to the server
        $postData = array(
                "USER"=> $this->user,
                "KEY"=> $this->apiKey,
                "VERSION"=> $this->version,
                "METHOD"=> "retrieve",
                "PID"=> $plagScanId,
                "MODE"=> $mode
        );


        $response = $this->submitPost($postData);
        if($response['status'] == 'ok') {
            switch($mode) {
                case 0:
                case 1:
                case 2:
                    $xmlObj = @simplexml_load_string(trim($response['content']));
                    if($xmlObj === false) {
                        return trim($response['content']);
                    } else {
                        return $xmlObj;
                    }
                    break;
                case 3:
                    $location = '/tmp/'.uniqid().'.docx';
                    $file = fopen($location, 'w');
                    fwrite($file, $response['content']);
                    fclose($file);
                    $return = $location;
                    $this->docXToDelete []= $location;
                    break;
                case 4:
                case 5:
                case 6:
                    $return = $response['content'];
                    break;
                default:
                    throw new Exception("Unknown mode $mode");
            }
            return $return;
        } else {
            throw new Exception($response['error']);
        }
    }

    /**
     * Submit an article for scanning
     *
     * @param string $text
     * @return string
     * @throws Exception
     */
    public function submitArticle($text, $filenamePrepend = null) {

        $filename = date('Y-m-d-g-i-a') . '.txt';
        if(!empty($filenamePrepend)) {
            $filename = $filenamePrepend . $filename;
        }
        $fileData = array('DATA', $filename, $text);

        $postData = array(
            "USER"=> $this->user,
            "KEY"=> $this->apiKey,
            "VERSION"=> $this->version,
            "METHOD"=> "submit"
        );

        $response = $this->submitFile($postData, $fileData);
        if($response['status'] == 'ok') {
            $xmlObj = simplexml_load_string($response['content']);
            return (string)$xmlObj->PID;
        } else {
            throw new Exception($response['error']);
        }
    }

    /**
     * Get account config info
     *
     * @return mixed
     * @throws Exception
     */
    public function getConfig() {
        $postData = array(
                "USER"=> $this->user,
                "KEY"=> $this->apiKey,
                "VERSION"=> $this->version,
                "METHOD"=> "getConfig"
        );
        $response = $this->submitPost($postData);
        if($response['status'] == 'ok') {
            $xmlObj = @simplexml_load_string(trim($response['content']));
            if($xmlObj === false) {
                return trim($response['content']);
            } else {
                return $xmlObj;
            }
        } else {
            throw new Exception($response['error']);
        }
    }

    /**
     *
     * @param array $postData
     * @param array $fileData
     * @return array
     */
    private function submitFile($postData, $fileData) {
        $data = "";
        $result = "";
        $errno = null;
        $errstr = null;
        $boundary = "---------------------".substr(md5(rand(0,32000)),0,10);
        // parse the given URL
        $url = parse_url($this->url);

        // extract host and path:
        $host = $url['host'];
        $path = $url['path'];

        // open a socket connection on port 443 - timeout: 300 sec
        $fp = fsockopen("ssl://".$host, 443, $errno, $errstr, 10);
        if($fp) {
            fputs($fp, "POST $path HTTP/1.0\n");
            fputs($fp, "Host: $host\n");
            fputs($fp, "Content-type: multipart/form-data; boundary=".$boundary."\n");

            // Ab dieser Stelle sammeln wir erstmal alle Daten in einem String
            // Sammeln der POST Daten
            foreach($postData as $key => $val){
                $data .= "--$boundary\n";
                $data .= "Content-Disposition: form-data; name=\"".$key."\"\n\n".$val."\n";
            }
            $data .= "--$boundary\n";

            // Sammeln der FILE Daten
            $data .= "Content-Disposition: form-data; name=\"{$fileData[0]}\"; filename=\"{$fileData[1]}\"\n";
            $data .= "Content-Type: image/jpeg\n";
            $data .= "Content-Transfer-Encoding: binary\n\n";
            $data .= $fileData[2]."\n";
            $data .= "--$boundary--\n";

            // Senden aller Informationen
            fputs($fp, "Content-length: ".strlen($data)."\n\n");
            fputs($fp, $data);

            // Auslesen der Antwort
            while(!feof($fp)) {
                $result .= fread($fp, 1);
            }
            // close the socket connection:
            fclose($fp);
        }
        else {
            return array(
                'status' => 'err',
                'error' => "$errstr ($errno)"
            );
        }


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

    /**
     *
     * @param array $postData
     * @return array
     */
    private function submitPost($postData) {
        $result = "";
        $errno = null;
        $errstr = null;

        // Convert the data array
        $postData = http_build_query($postData); //for some PHP versions use: http_build_query($data, '', '&');

        // parse the given URL
        $url = parse_url($this->url);

        // extract host and path:
        $host = $url['host'];
        $path = $url['path'];

        // open a socket connection on port 443 - timeout: 30 sec
        $fp = fsockopen("ssl://".$host, 443, $errno, $errstr, 10);

        if ($fp){

            // send the request headers:
            fputs($fp, "POST $path HTTP/1.0\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ". strlen($postData) ."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $postData);

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

    /**
     * destroy any generated docx files
     */
    public function __destruct() {
        foreach($this->docXToDelete as $file) {
            if(is_file($file) && !is_dir($file)) {
                unlink($file);
            }
        }
    }
}