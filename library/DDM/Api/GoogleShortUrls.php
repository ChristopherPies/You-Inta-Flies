<?php
class DDM_Api_GoogleShortUrls
{
    //for key info go to https://code.google.com/apis/console/#project:490988523268:team
    //add your server's ip to the API Access list 
    //msticht and sdickson are owners and can get you access
    //Only users in domain deseretdigital.com may be added to the project.
    //put google.shorturl.key  = "YOUR KEY" in application.ini
    private $curlUrl = 'https://www.googleapis.com/urlshortener/v1/url';
    
    public function __construct()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options = $bootstrap->getOptions();
        $googleConfig = $options['google'];
        $this->curlUrl .= '?key='.$googleConfig['shorturl']['key'];
    }
    
    /**
     * Gets a short url from goo.gl
     * 
     * @param string $longUrl
     * @return string
     * @throws Exception
     */
    public function getShortUrl($longUrl)
    {
        $ch = curl_init($this->curlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('longUrl'=>$longUrl)));
        $result = json_decode(curl_exec($ch),true);
        if(empty($result['error'])) {
            return $result['id'];
        } else {
            throw new Exception($result['error']['message']);
        }
    }
    
    /**
     * Gets a long url from goo.gl
     * 
     * @param string $shortUrl
     * @return string
     * @throws Exception
     */
    public function getLongUrl($shortUrl)
    {
        $ch = curl_init($this->curlUrl."&shortUrl=$shortUrl");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch),true);
        if($result['status'] == 'OK') {
            return $result['longUrl'];
        } else {
            throw new Exception($result['error']['message']);
        }
    }
    
    /**
     * Gets the analytical information for a short url from goo.gl
     * 
     * @param string $shortUrl
     * @return array
     * @throws Exception
     */
    public function getAnalytics($shortUrl) 
    {
        $ch = curl_init($this->curlUrl."&shortUrl=$shortUrl&projection=FULL");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch),true);
        if($result['status'] == 'OK') {
            return $result;
        } else {
            throw new Exception($result['error']['message']);
        }
    }
}
?>
