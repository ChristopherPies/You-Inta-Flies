<?php

class DDM_Api_Ooyala_Client
{
    protected $apiKey;
    protected $secret;
    protected $baseUrl = 'https://api.ooyala.com/';
    protected $messages;

    public function __construct($apiKey, $secret, $baseUrl = null)
    {
        if (!is_null($apiKey)) {
            $this->apiKey = $apiKey;
        }

        if (!is_null($secret)) {
            $this->secret = $secret;
        }

        if (!is_null($baseUrl)) {
            $this->baseUrl = $baseUrl;
        }

        $this->messages = array();
    }

    /**
     * Get the debug messages that have been set
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Clear out the debug messages
     */
    public function clearMessages()
    {
        $this->messages = array();
    }

    /**
     * Send the request to the Ooyala server
     * @param string $path
     * @param string $method
     * @param string $body
     * @param array $parameters
     * @param boolean $return
     * @return mixed
     */
    public function request($path, $method, $body = null, $parameters = null, $return = false)
    {
        $method = strtoupper($method);

        switch ($method) {
            case 'GET':
                $url = $this->generateUrl($path, $method, $parameters);
                $response = $this->curlGet($url);
                break;
            case 'PUT':
                $url = $this->generateUrl($path, $method, $parameters, $body);
                $response = $this->curlPut($url, $body, $return);
                break;
            case 'POST':
                $url = $this->generateUrl($path, $method, $parameters, $body);
                $response = $this->curlPost($url, $body, $return);
                break;
            case 'PATCH':
                $url = $this->generateUrl($path, $method, $parameters, $body);
                $response = $this->curlPatch($url, $body, $return);
                break;
            case 'DELETE':
                $url = $this->generateUrl($path, $method, $parameters, $body);
                $response = $this->curlDelete($url);
                break;
            case 'UPLOAD':
                $response = $this->curlUpload($path, $body, $return);
                break;
            default:
                $response = false;
        }

        return $response;
    }

    /**
     *
     * @param string $path
     * @param string $method
     * @param mixed $parameters array or null (optional)
     * @param string $body
     * @return string
     */
    private function generateUrl($path, $method, $parameters = null, $body = null)
    {
        if (is_null($parameters)) {
            $parameters = array();
        }

        if (!array_key_exists('expires', $parameters)) {
            $parameters['expires'] = time() + 900;
        }
        $parameters['api_key'] = $this->apiKey;

        $signature = $this->secret . $method . '/' . $path;
        $url = $this->baseUrl . $path . '?';

        $keys = array_keys($parameters);
        sort($keys);

        $qs = array();

        foreach ($keys as $key) {
            $signature .= $key . '=' . $parameters[$key];
            $qs[] = rawurlencode($key) . '=' . rawurlencode($parameters[$key]);
        }

        $url .= implode('&', $qs);

        if (!is_null($body)) {
            $signature .= $body;
        }

        $digest = hash('sha256', $signature, true);
        $signature = preg_replace('/=+$/', '', substr(trim(base64_encode($digest)), 0, 43));

        $url .= '&signature=' . rawurlencode($signature);

        return $url;
    }

    /**
     *
     * @param string $url
     * @return mixed false or response
     */
    private function curlGet($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            return $response;
        } else {
            $this->messages['response'] = $response;
            $this->messages['request'] = $info;
            return false;
        }
        curl_close($ch);
    }

    /**
     *
     * @param type $url
     * @param type $body
     * @param type $return
     * @return boolean
     */
    private function curlPatch($url, $body = null, $return = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            if ($return) {
                return $response;
            }
            return true;
        } else {
            $this->messages['response'] = $response;
        	$this->messages['request'] = $info;
            return false;
        }
        curl_close($ch);
    }

    /**
     *
     * @param type $url
     * @return boolean
     */
    private function curlDelete($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            return true;
        } else {
            $this->messages['response'] = $response;
            $this->messages['request'] = $info;
            return false;
        }
        curl_close($ch);
    }

    /**
     *
     * @param string $url
     * @param type $body
     * @param boolean $return
     * @return mixed boolean or response
     */
    private function curlPut($url, $body = null, $return = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            if ($return) {
                return $response;
            }
            return true;
        } else {
            $this->messages['response'] = $response;
        	$this->messages['request'] = $info;
            return false;
        }
        curl_close($ch);
    }

    /**
     *
     * @param string $url
     * @param type $body
     * @param boolean $return
     * @return mixed boolean or response
     */
    private function curlPost($url, $body, $return = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            if ($return) {
                return $response;
            }
            return true;
        } else {
            $this->messages['response'] = $response;
            $this->messages['request'] = $info;
            $this->messages['body'] = $body;
        	return false;
        }
        curl_close($ch);
    }

    /**
     *
     * @param string $destination
     * @param string $path
     * @param boolean $return
     * @return mixed boolean or response
     */
    private function curlUpload($url, $body, $return = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('file' => "@$body"));

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        // 05/17/2013 - Ooyala changed this to 204 instead of 200 without telling us. :(
        if ($info['http_code'] == 204) {
            if ($return) {
                return $response;
            }
            return true;
        } else {
            $this->messages['response'] = $response;
            $this->messages['request'] = $info;
            return false;
        }
        curl_close($ch);
    }
}