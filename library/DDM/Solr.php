<?php
require_once '/usr/local/export/ksl/v2/jobs/library/Debug.php';

/**
 * This is a general solr class.
 * It should handle basic solr functionality.
 */

require_once "/usr/local/dado/core/classes/mcache.php";

/**
 *
 */
class DDM_Solr
{
    private $_core;
    private $solrMasterURL;
    private $solrMasterPort;

    /**
     * This is the
     * constructor not to be confused with a boa constrictor
     *
     * @param string $core           The solr core you are going to connect to
     * @param string $solrMasterURL  The URL of the master solr server
     * @param string $solrMasterPort The port of the solr server you are connection to
     */
    public function __construct($core, $solrMasterURL, $solrMasterPort = '8983')
    {
        $this->_core = $core;
        $this->solrMasterURL = $solrMasterURL;
        $this->solrMasterPort = $solrMasterPort;
    }

    /**
     *
     */
    private function selectSlave()
    {
        $key = 'solr_repl_status';
        $mcd = mcache::getInstance();
        $status_array = @json_decode($mcd->get($key));
        $path = "/usr/local/global/class/solr_repl.txt";

        if (count($status_array)) {
            //loop through the memcache data
            foreach ($status_array AS $ip => $status) {
                if($status == "up")
                $choices[] = $ip;
            }
        } else {
            //fallback to the file and fetch the info
            $fh = fopen($path, "r");

            while ($line = fgets($fh)) {
                $line = trim($line);
                if($line == '')
                    continue;

                list($ip, $status) = explode("|", $line);

                if($status == "up")
                    $choices[] =$ip;
            }
            fclose($fh);
        }

        $rand = rand(0, (count($choices)-1));

        return $choices[$rand];
    }

    /**
     *
     */
    private function queryMaster($action, $data = '')
    {
        $curl = curl_init();

        //build the URL to post to
        $url = $this->solrMasterURL.":".$this->solrMasterPort."/solr/".$this->_core."/".$action;
        //echo "URL: ".$url."\n";

        $header = array("Content-type:text/xml; charset=utf-8");

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLINFO_HEADER_OUT, 1);

        //execute the query
        $curl_return = curl_exec($curl);

        $error = curl_error($curl);
        //echo "\n\n".strlen($error)."\n\n";
        if (strlen($error) > 0) {
            //echo "Error\n";
            //echo "XML: \n".$data;
            //echo "\n\n";
            //$error = curl_error($curl);
            $return = array("status" => 0, "data" => $error);
            curl_close($curl);
            return $return;
        }
        $return = array("status" => 1, "data" => $curl_return);
        curl_close($curl);
        return $return;
    }

    /**
     *
     */
    public function addFromXML($xml)
    {

        $xml = "<add>".$xml."</add>";

        $response = $this->queryMaster("update", $xml);
/*
		$fh = fopen("/tmp/solrLog.txt", "a+");
		fwrite($fh, "-----------------------------------------------------------------\n");
		fwrite($fh, date("Y-m-d H:i:s")."\n");
		fwrite($fh, $xml."\n");
		fwrite($fh, json_encode($response)."\n\n");

		fclose($fh);
*/
        return json_encode($response);
    }

    /**
     * This is the search functionality of the class
     *
     * @param string $returnFields   Fields to return - can use * or comma separated list
     * @param string $start          The result number to start at
     * @param string $resultsPerPage Number of results to return
     * @param array  $q              Array of terms / items to search for can be <field name>:<value> or just term to search
     *                               to see all results you will probably want to to do something like create_time:[* TO NOW]
     *                               which would limit the items to those created up until now
     * @param array  $fq             This is a search refiner - used for geolocation searchs
     * @param string $sort           How to sort the results - similar to mysql - <field name> <asc / desc>
     * @param array  $facetFields    Fields to return facet information on
     * @param array  $facetQueries   To perform facet queries such as time ranges or distance - array("field" => array("range 1", "range 2"))
     * @param bool   $searchMaster   This should be done very sparingly
     * @param array  $urlparams      Any additional parameters for Solr query, go to query string as is - array('facet.<field name>.mincount=1')
     *
     * @return array $data           Array with the following params:
     *                                  - count - number of results in the total set, not those returned
     *                                  - items - the results to be returned - array(index => array(), index => array())
     *                                  - facets - array(fieldName => array( facetOption => count, facetOption => count))
     */
    public function search($returnFields, $start, $resultsPerPage, $q, $fq, $sort, $facetFields, $facetQueries, $queryMaster = false, $urlparams = array())
    {
        // build solr query parameters
        $p = array();
        $p['wt'] = 'json';      // display format
        $p['fl'] = $returnFields;      // fields to display
        if (is_array($q) && count($q) > 0) {
            $p['q'] = urlencode(join(' ', $q));
            $p['q'] = str_replace("%26quot%3B", "%22", $p['q']);
        }
        if (is_array($fq) && count($fq) > 0) {
            $p['fq'] = urlencode(join(' ', $fq));
            //$p['fq'] .= urlencode(join(' ',$fq));
        }
        $p['sort'] = urlencode($sort);
        $p['start'] = $start;

        $p['rows'] = $resultsPerPage;
        $p['facet'] = "true";
        //the default is 100 - not sure why it limits the data, there is no way to get more facets that I know of
// changed to 10000 on 05/21/2014 by Victor Savieliev
// otherwise company listings cannot be fully retrieved
        $p['facet.limit'] = 10000;

        if (is_array($urlparams))
        foreach($urlparams as $param => $value)
            $p[$param] = $value;

        //echo "fq: ".$p['fq']."<br />";
        //echo "q: ".$p['q']."<br /><br />";

        // create parameter strings
        $pstrs = array();
        foreach ($p as $name => $value) {
                $pstrs[] = "$name=$value";
        }
        $pstr = join('&', $pstrs);

        if($queryMaster == true)
            $solr_ip = $this->solrMasterURL;
        else
            $solr_ip = "http://".$this->selectSlave();

        //echo $solr_ip."<br />";
        //$url = "http://".$solr_ip.":8983/solr/".$this->_core."/select/?$pstr";
        $url = $solr_ip.":8983/solr/".$this->_core."/select/?$pstr";

        //facets
        //added extra logic to prevent SOLR complaining about errors
        if (count($facetFields)) {
            foreach ($facetFields AS $field) {
                if($field != '')
                    $url .= "&facet.field=".$field;
            }
        }

        //facet queries
        if (count($facetQueries)) {
            foreach ($facetQueries AS $field => $values) {
                foreach ($values AS $value) {
                    if ($field == "") {
                        $url .= "&facet.query=".urlencode($value);
                    } else {
                        // identify if the value needs to be bracketed (found the word TO in the value, but no braces on each end)
                        if (preg_match('/ TO /i', $value) && !preg_match('/^[[{].*[\]}]$/i', $value))
                        {
                            $url .= "&facet.query=".urlencode($field.":[".$value."]");
                        }
                        else
                        {
                            $url .= "&facet.query=".urlencode($field.":".$value);
                        }
                    }
                }
            }
        }
        //$url .= "&facet.query=".urlencode("display:[NOW-1HOURS TO NOW]");

        //zip code facets
        //$fq[] = "{!geofilt pt=".$lat.",".$lon." sfield=item_latlon d=".$distance."}";
/*
        if($zipdata != false)
        {
                $url .= "&facet.query=".urlencode("zip:$zipcode");
                list($lat, $lon) = explode("|", $zipdata);
                //$distances = array(10, 25, 50, 100, 150, 200, 250, 10000);
                $distances = array(10, 25, 50, 100, 150, 200, 10000);
                //$distances = array(10, 25, 50, 100, 150, 200, 10000);
                foreach($distances AS $dist_val)
                {
                        $url .= "&facet.query=".urlencode("{!geofilt pt=".$lat.",".$lon." sfield=item_latlon d=".$dist_val."}");
                }
        }
*/
	        //---------------------Display Solr Query-------------------------
//        echo "<p style='width: 400px;'>".urldecode($url)."</p><br /><br />";
//$this->_log(urldecode($url));
        //echo "<p>".$url."<br /></p>";
        //echo "<!-- URL: $url -->\n";
        //echo $url."\n\n
//Debug::log($url, '* solr');

        //-------make the solr call
        $dataRaw = @file_get_contents($url);
//print_r($dataRaw);
        //$dataRaw = file_get_contents($url);
        if ($dataRaw === false) {
            //echo "no data<br />";
            return false;
        }

        $data = @json_decode($dataRaw, true);
        if($data === false)
                return false;

        $list = array();
        $list['count'] = $data['response']['numFound'];
        $list['items'] = $data['response']['docs'];
		$list['solrIp'] = $solr_ip;

        //print_r($data);
        //echo "<br /><br />";

        //facets
        $facets = array();
        foreach ($data['facet_counts']['facet_fields'] AS $field => $info) {
            //echo $field."<br />";
            for ($x = 0; $x < count($info); $x++) {
                $key = $info[$x++];
                $value = $info[$x];
                $facets[$field][$key] = $value;
            }
            //print_r($facets);
        }

        foreach($data['facet_counts']['facet_queries'] AS $field => $info)
        {
                if(strpos($field, ":") > 0)
                {
                        list($field, $range) = explode(":", $field);
                        if($field == "zip")
                                $facets['geofilt'][0] = $info;
                        else
                                $facets[$field][$range] = $info;
                }
                elseif(substr($field, 0, 9) == "{!geofilt")
                {
                    $geofilts = explode(" ", $field);
                    $facet_geofilt = substr($geofilts[3], 2, strlen($geofilts[3]) - 3);
                    //echo $field." - ".$info." - ".$facet_geofilt."<br />";
                    $facets['geofilt'][$facet_geofilt] = $info;
                } else {
// case when facet queries aren't tied to field name and may have their own
                    $facets[$field] = $info;
                }
                //echo $field." - ".$range." - ".$info."<br />";
        }

        $list['facets'] = $facets;

        return $list;
    }

    public function delete($key, $value)
    {
        if(strlen($key) == 0)
        {
            return array("status" => 0, "data" => 'must have a key field');
        }

        $xml = "<delete>";
        $xml .= "<".$key.">".$value."</".$key.">";
        $xml .= "</delete>";

        $response = $this->queryMaster("update", $xml);

        //commit the changes
        $this->commit();

        return json_encode($response);
    }

    public function commit()
    {
        $xml = "<commit />";
        $response = $this->queryMaster("update", $xml);
        return json_encode($response);
    }

    public function optimize()
    {
        $xml = "<optimize />";
        $response = $this->queryMaster("update", $xml);
        return json_encode($response);
    }

    public function deleteAllItems()
    {
        $xml = "<delete><query>*:*</query></delete>";
        $response = $this->queryMaster("update", $xml);
        return json_encode($response);
    }

    function _log($data) {
        $fp = fopen('/var/www/ksl-api/log', 'a');
        ob_start();
        print_r($data);
        fwrite($fp, ob_get_clean() . "\n");
        fclose($fp);
    }
}
?>
