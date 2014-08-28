<?php

class DDM_Json_Client_Response extends Zend_Json_Server_Response
{
    /**
     * initialize JSON-RPC object with JSON response from server
     * @param string $json
     * @return void
     */
    public function loadJson($json)
    {
        $response = Zend_Json::decode($json);
        
        if(isset($response['error'])) {
            $this->setError(new Zend_Json_Server_Error(
                $response['error']['message'],
                $response['error']['code'],
                $response['error']['data']
            ));
        } else {
            $this->setResult($response['result']);
        }
        
        if (!empty($response['jsonrpc'])) {
            $this->setVersion($response['jsonrpc']);
        }
        
        if (!empty($response['id'])) {
            $this->setId($response['id']);
        }
    }
}