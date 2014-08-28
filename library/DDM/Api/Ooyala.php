<?php

/**
 * Implements Ooyala API V2 functionality
 * Author: Jeremy Hicks
 * Date: January 20, 2012
 *
 * Chunking of files has not been tested.
 */

class DDM_Api_Ooyala
{
    protected $chunkSize = 65536;
    protected $messages;
    protected $client;

    /**
     *
     * @param int $chunkSize (optional)
     */
    public function __construct($client)
    {
        $this->messages = array();

        $this->client = $client;
    }

    /**
     * Get the debug messages that have been set
     * @return mixed
     */
	public function getMessages()
	{
        $clientMessages = $this->client->getMessages();

        $messages = array_merge($clientMessages, $this->messages);

		return $messages;
	}

    /**
     * Clear out the debug messages
     */
    public function clearMessages()
    {
        $this->client->clearMessages();
        $this->messages = array();
    }

    /**
     * Create the video.
     * @param string $path
     * @param array $parameters
     * @param boolean $chunking (optional)
     * @param string $postProcessingStatus
     * @return mixed false or embed code
     */
    public function createVideo($path, $options, $chunking = false, $postProcessingStatus =  null)
    {
        $uploadStatus = 'uploaded';

        $fileSize = filesize($path);
        if ($fileSize == 0) {
            $this->messages[] = 'File ' . $path . ' was empty';
            return false;
        }

        $parameters = array(
            'file_name' => basename($path),
            'asset_type' => 'video',
            'file_size' => $fileSize
        );

        $parameters = array_merge_recursive($parameters, $options);

        if (!array_key_exists('description', $parameters)) {
            if (array_key_exists('name', $parameters)) {
                $parameters['description'] = $parameters['name'];
            }
        }

        if ($chunking) {
            $parameters['chunk_size'] = $this->chunkSize;
        }

        if (!is_null($postProcessingStatus)) {
            $parameters['post_processing_status'] = $postProcessingStatus;
        }

        $body = json_encode($parameters);
        $response = $this->client->request('v2/assets', 'POST', $body, null, true);

        if (!$response) {
            $this->messages[] = 'Could not create video';
            return false;
        }

        $data = json_decode($response, true);
        $uploadUrls = $this->getUploadUrls($data['embed_code']);

        if (!$uploadUrls) {
            $this->messages[] = 'Could not get upload URLs';
            return false;
        }

        if ($chunking) {
            // Need to split file here...
            $numberOfChunks = $this->createChunks($path);
            $counter = 1;

            foreach ($uploadUrls as $destination) {
                $response = $this->client->request($destination, 'UPLOAD', $path . '_' . $counter);

                if (!$response) { // Problem uploading chunk...
                    $this->messages[] = 'Could not upload chunk';
                    $uploadStatus = 'failed';
                    break;
                }

                $counter++;
            }

            $this->deleteChunks($path, $numberOfChunks);
        } else {
            $destination = $uploadUrls[0];
            $response = $this->client->request($destination, 'UPLOAD', $path);

            if (!$response) {
                $this->messages[] = 'Could not upload file';
                $uploadStatus = 'failed';
            }
        }

        $response = $this->setUploadStatus($data['embed_code'], $uploadStatus);

        if (!$response) {
            $this->messages[] = 'Could not set upload status';
            return false;
        }

        // We want to know if this is failing!
        if ($uploadStatus == 'failed') {
            return false;
        }

        // Change to return eveything?
        return $data['embed_code'];
    }

    /**
     * Create a live stream
     * @param string $name
     * @param string $type
     * @param string $primaryEncoderIp
     * @param string $password
     * @param array $encodings
     * @return mixed
     */
    public function createLiveStream($name, $type, $primaryEncoderIp, $password, array $encodings = null)
    {
        if (is_null($encodings)) {
            $encodings = array(
                array('width' => 480, 'height' => 270, 'bitrate' => 348),
                array('width' => 640, 'height' => 360, 'bitrate' => 996),
                array('width' => 960, 'height' => 540, 'bitrate' => 1828),
                array('width' => 1280, 'height' => 720, 'bitrate' => 3092),
            );
        }

        $options = array(
            'primary_encoder_ip' => $primaryEncoderIp,
            'password' => $password,
            'is_flash' => true,
            'is_ios' => true,
            'encodings' => $encodings
        );

        return $this->createAsset($name, $type, $options);
    }

    /**
     * Update an asset's values
     * @param string $assetId
     * @param array $options
     * @return boolean
     */
    public function updateAsset($assetId, $options)
    {
        $body = json_encode($options);
        $response = $this->client->request('v2/assets/' . $assetId, 'PATCH', $body);

        if (!$response) {
            $this->messages[] = 'Could not update asset';
            return false;
        }

        return true;
    }

    /**
     * Assign a label to an asset. Label should include prepended forward slash.
     * @param string $assetId
     * @param string $label
     * @return boolean
     */
    public function assignLabel($assetId, $label, $createLabel = true)
    {
        // First have to get the label id for the label we want
        $labelId = $this->getLabelId($label, $createLabel);

        if (!$labelId) {
            return false;
        }

        $response = $this->client->request('v2/assets/' . $assetId . '/labels/' . $labelId, 'PUT', '');

        return $response;
    }

    /**
     * Assign a label to an asset using the label ID directly
     * @param string $assetId
     * @param string $labelId
     * @return boolean
     */
    public function assignLabelId($assetId, $labelId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/labels/' . $labelId, 'PUT', '');

        return $response;
    }

    /**
     * Assign an ad set to a video, you can't assign an ad set to a channel
     * @param string $assetId
     * @param string $adSetId
     * @return boolean
     */
    public function assignAdSet($assetId, $adSetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/ad_set/' . $adSetId, 'PUT', '');

        return $response;
    }

    /**
     * Unassign the ad set assigned to an asset.
     * @param string $assetId
     * @return boolean
     */
    public function unassignAdSet($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/ad_set', 'DELETE', '');

        return $response;
    }

    /**
     * Get all ad sets
     * @return mixed
     */
    public function getAdSets()
    {
        $response = $this->client->request('v2/ad_sets', 'GET');

        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data;
    }

    /**
     * Get the ad set assigned to a video
     * @param string $assetId
     * @return mixed
     */
    public function getAssignedAdSet($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/ad_set', 'GET');

        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data;
    }

    /**
     * Set the closed captions for the asset
     * @param string $assetId
     * @param string $file
     * @return boolean
     */
    public function setClosedCaptions($assetId, $file)
    {
        if (!file_exists($file)) {
            return false;
        }

        $body = file_get_contents($file);
        $response = $this->client->request('v2/assets/' . $assetId . '/closed_captions', 'PUT', $body);

        return $response;
    }

    /**
     * Get a video
     * @param string $assetId
     * @return mixed
     */
    public function getVideo($assetId)
    {
        return $this->getAsset($assetId);
    }

    /**
     * Get an asset
     * @param string $assetId
     * @return mixed
     */
    public function getAsset($assetId, $parameters = null)
    {
        $response = $this->client->request('v2/assets/' . $assetId, 'GET', null, $parameters);

        if (!$response) {
            $this->messages[] = 'Could not get asset';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get assets by date range
     * @param string $start
     * @param string $type
     * @return mixed
     */
    public function getAssetsByDateRange($start, $type = 'video', $limit = null)
    {
        $start = date('c', strtotime($start));

        $parameters = array(
            'where' => "created_at>'$start' AND status='live' AND asset_type='$type'",
            'orderby' => "created_at+DESCENDING"
        );

        if (!is_null($limit)) {
            $parameters['limit'] = $limit;
        }

        $response = $this->client->request('v2/assets', 'GET', null, $parameters);

        if (!$response) {
            $this->messages[] = 'Could not get assets';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get assets assigned to a label
     * @param string $labelId
     * @param array $parameters
     * @return mixed
     */
    public function getAssetsByLabel($labelId, $parameters = null)
    {
        $response = $this->client->request('v2/labels/' . $labelId . '/assets', 'GET', null, $parameters);

        if (!$response) {
            $this->messages[] = 'Could not get assets by label';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get assets based on search parameters
     * @param array $parameters
     * @return mixed
     */
    public function getAssets($parameters)
    {
        $response = $this->client->request('v2/assets', 'GET', null, $parameters);

        if (!$response) {
            $this->messages[] = 'Could not get assets';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get the labels for an asset
     * @param string $assetId
     * @return mixed
     */
    public function getLabelsByAsset($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/labels', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get labels by asset';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get all syndications. Wish there was a way to specify type
     * @return mixed
     */
    public function getSyndications()
    {
        $response = $this->client->request('v2/syndications', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get syndications';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get a syndication's details
     * @param string $syndicationId
     * @return mixed
     */
    public function getSyndication($syndicationId)
    {
        $response = $this->client->request('v2/syndications/' . $syndicationId, 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get syndication: ' . $syndicationId;
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get streams of an asset
     * @param string $assetId
     * @return mixed
     */
    public function getStreams($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/streams', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get streams';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get all the players
     * @return mixed
     */
    public function getPlayers()
    {
        $response = $this->client->request('v2/players', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get players';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get player by name
     * @param string $name
     * @return mixed
     */
    public function getPlayerByName($name = 'Default Player')
    {
        $players = $this->getPlayers();

        if (!$players) {
            return false;
        }

        foreach ($players['items'] as $player) {
            if ($player['name'] == $name) {
                return $player['id'];
            }
        }

        // Could not find player
        return false;
    }

    /**
     * Assign a player to an assset
     * @param string $assetId
     * @param sring $playerId
     * @return type
     */
    public function assignPlayerToAsset($assetId, $playerId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/player/' . $playerId, 'PUT', '');

        if (!$response) {
            $this->messages[] = 'Could not assign player to asset';
        }

        return $response;
    }

    /**
     *
     * @param string $channel
     * @return mixed
     */
    public function getChannel($channel, $createChannel = false)
    {
        $parameters = array('where' => "name='$channel'");
        $response = $this->client->request('v2/assets', 'GET', null, $parameters);

        $asset = json_decode($response, true);

        if (empty($asset['items'])) {
            if ($createChannel) {
                $asset = $this->createChannel($channel);
                if (!$asset) {
                    $this->messages[] = 'Could not create channel';
                    return false;
                }
            } else {
                $this->messages[] = 'Could not find channel';
                return false;
            }
        }

        return $asset;
    }

    /**
     * Get the channel lineup of videos
     * @param string $assetId
     * @return mixed
     */
    public function getChannelLineup($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/lineup', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get channel lineup';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     *
     * @param string $channel
     * @param string $videoEmbedCode
     * @return boolean
     */
    public function addVideoToChannel($channel, $videoEmbedCode, $createChannel = false)
    {
        $asset = $this->getChannel($channel, $createChannel);
        if (!$asset) {
        	$this->messages[] = 'Could not get channel';
        	return false;
        }

        if (array_key_exists('embed_code', $asset)) {
        	$channelEmbedCode = $asset['embed_code'];
        } else {
        	$channelEmbedCode = $asset['items'][0]['embed_code'];
        }

        $response = $this->client->request('v2/assets/' . $channelEmbedCode . '/lineup/' . $videoEmbedCode, 'PUT', '');

        if (!$response) {
            $this->messages[] = 'Could not do curl put';
            return false;
        }

        return $response;
    }

    /**
     *
     * @param string $channel
     * @return mixed
     */
    public function createChannel($channel)
    {
        $data = $this->createAsset($channel, 'channel');
        if (!$data) {
        	$this->messages[] = 'Could not create asset';
        	return false;
        }

        return $data;
    }

    /**
     * Create an asset
     * @param string $name
     * @param string $type
     * @param array $options
     * @return mixed false or asset data
     */
    public function createAsset($name, $type, $options = null)
    {
        $parameters = array(
            'name' => $name,
            'asset_type' => $type
        );

        if (!is_null($options)) {
            $parameters = array_merge_recursive($parameters, $options);
        }

        $body = json_encode($parameters);
        $response = $this->client->request('v2/assets', 'POST', $body, null, true);

        if (!$response) {
            $this->messages[] = 'Could not create asset';
            return false;
        }

        $data = json_decode($response, true);

        return $data;
    }

    /**
     * Create a YouTube asset
     * @param string $name
     * @param string $youTubeId
     * @return mixed
     */
    public function createYouTubeAsset($name, $youTubeId)
    {
        return $this->createAsset($name, 'youtube', array('youtube_id' => $youTubeId));
    }

    /**
     * Create remote asset, for some reason flash stream url is required
     * Valid stream urls are flash, iphone, ipad, itunes, source_file
     * @param string $name
     * @param int $duration
     * @param string $flashStreamUrl
     * @param array $options
     * @return mixed
     */
    public function createRemoteAsset($name, $duration, $flashStreamUrl, $options = null)
    {
        $parameters = array(
            'duration' => $duration,
            'stream_urls' => array(
                'flash' => $flashStreamUrl
            )
        );

        if (!is_null($options)) {
            $parameters = array_merge_recursive($parameters, $options);
        }

        return $this->createAsset($name, 'remote_asset', $parameters);
    }

    /**
     * Supposed to return the current upload images but is returning nothing.
     * @param string $path
     * @param string $embedCode
     * @return boolean
     */
    public function uploadPromoImage($path, $embedCode, $width = 640, $height = 360, $setImage = true)
    {
        $parameters = array(
            'width' => $width,
            'height' => $height,
            'url' => $path
        );

        $body = json_encode($parameters);
        $response = $this->client->request('v2/assets/' . $embedCode . '/preview_image_urls', 'POST', $body);

        if (!$response) {
        	$this->messages[] = 'Could not upload promo image';
            return false;
        }

        if ($setImage) {
            return $this->setPreviewImage($embedCode);
        } else {
            return true;
        }
    }

    public function uploadPromoImageFile($path, $embedCode, $setImage = true)
    {
        $body = file_get_contents($path);
        $response = $this->client->request('v2/assets/' . $embedCode . '/preview_image_files', 'POST', $body);

        if (!$response) {
        	$this->messages[] = 'Could not upload promo image file';
            return false;
        }

        if ($setImage) {
            return $this->setPreviewImage($embedCode, 'uploaded_file');
        } else {
            return true;
        }
    }

    /**
     * Set the preview image to whatever Ooyala thinks is the best size of the remote images we tell them to reference.
     * @param string $embedCode
     * @return boolean
     */
    private function setPreviewImage($embedCode, $type = 'remote_url', $time = null)
    {
        $parameters = array(
            'type' => $type
        );

        if (!is_null($time)) {
            $parameters['time'] = $time;
        }

        $body = json_encode($parameters);
        $response = $this->client->request('v2/assets/' . $embedCode . '/primary_preview_image', 'PUT', $body);

        if (!$response) {
            $this->messages[] = 'Could not set preview image';
        }

        return $response;
    }

    /**
     * Get the preview images for an asset
     * @param string $embedCode
     * @return array
     */
    public function getPreviewImageUrls($embedCode)
    {
        $response = $this->client->request('v2/assets/' . $embedCode . '/preview_image_urls', 'GET');

        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data;
    }

    /**
     * Get the primary preview image
     * @param string $embedCode
     * @return array
     */
    public function getPrimaryPreviewImage($embedCode)
    {
        $response = $this->client->request('v2/assets/' . $embedCode . '/primary_preview_image', 'GET');

        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data;
    }

    /**
     * Get all the labels
     * @param int $limit
     * @return mixed
     */
    public function getLabels($limit = 250)
    {
        $parameters = array('limit' => $limit);

        $response = $this->client->request('v2/labels', 'GET', null, $parameters);

        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data;
    }

    /**
     * Get the label id
     * The $label parameter is the full name of the label including parent labels
     * and forward slashes.
     * @param string $label
     * @param boolean $createLabel
     * @return mixed
     */
    public function getLabelId($label, $createLabel = false)
    {
        $labelId = false;

        $response = $this->client->request('v2/labels/by_full_path/' . urlencode('/' . $label), 'GET');

        if ($response != false) {
            $labels = json_decode($response, true);

            foreach ($labels['items'] as $l) {
                if ($l['full_name'] == '/' . $label) {
                    $labelId = $l['id'];
                    break;
                }
            }
        }

        // Could not find the label and want to create it.
        if ($createLabel && !$labelId) {
            $pieces = explode('/', $label);
            $parentLabel = null;

            if (count($pieces) == 1) {
                $labelId = $this->createLabel($label, $parentLabel);

                if (!$labelId) {
                    return false;
                }
            } else {
                foreach ($pieces as $label) {
                    // Reset for next loop iteration...
                    $labelId = false;

                    if (is_null($parentLabel)) {
                        $labelId = $this->getLabelId($label, true);
                    }

                    if (!$labelId) {
                        $labelId = $this->createLabel($label, $parentLabel);

                        if (!$labelId) {
                            return false;
                        }
                    }

                    $parentLabel = $label;
                }
            }
        }

        return $labelId;
    }

    /**
     * Create a label when it doesn't exist
     * Returns the newly created label's id or false.
     * @param string $label
     * @param string $parentLabel
     * @return mixed
     */
    public function createLabel($label, $parentLabel = null)
    {
        $parameters = array(
            'name' => $label
        );

        if (!is_null($parentLabel)) {
            $parentLabelId = $this->getLabelId($parentLabel);

            if (!$parentLabelId) {
                $parentLabelId = $this->createLabel($parentLabel);

                if (!$parentLabelId) {
                    return false;
                }
            }

            $parameters['parent_id'] = $parentLabelId;
        }

        $body = json_encode($parameters);
        $response = $this->client->request('v2/labels', 'POST', $body, null, true);

        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data['id'];
    }

    /**
     * Delete an asset
     * @param type $assetId
     * @return boolean
     */
    public function deleteAsset($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId, 'DELETE');

        return $response;
    }

    /**
     * Delete a label
     * @param string $labelId
     * @return boolean
     */
    public function deleteLabel($labelId)
    {
        $response = $this->client->request('v2/labels/' . $labelId, 'DELETE');

        return $response;
    }

    /**
     *
     * @param int $assetId
     * @return mixed false or upload urls
     */
    private function getUploadUrls($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/uploading_urls', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get upload URLs';
            return false;
        }
        return json_decode($response, true);
    }

    /**
     *
     * @param int $assetId
     * @param string $status
     * @return boolean
     */
    public function setUploadStatus($assetId, $status = null)
    {
        if (is_null($status)) {
            $status = 'uploaded';
        }
        $parameters = array('status' => $status);
        $body = json_encode($parameters);

        $response = $this->client->request('v2/assets/' . $assetId . '/upload_status', 'PUT', $body);

        if (!$response) {
            $this->messages[] = 'Could not set upload status';
            return false;
        }

        return true;
    }

    /**
     * Get the metadata for an asset
     * @param string $assetId
     * @return mixed
     */
    public function getMetadata($assetId)
    {
        $response = $this->client->request('v2/assets/' . $assetId . '/metadata', 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get metadata';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Set the metadata for an asset
     * @param string $assetId
     * @param array $parameters
     * @return boolean
     */
    public function setMetadata($assetId, $parameters)
    {
        $body = json_encode($parameters);
        $response = $this->client->request('v2/assets/' . $assetId . '/metadata', 'PUT', $body);

        if (!$response) {
            $this->messages[] = 'Could not set metadata';
            return false;
        }

        return true;
    }

    /**
     * Update metadata for an asset
     * @param string $assetId
     * @param array $parameters
     * @return boolean
     */
    public function updateMetadata($assetId, $parameters)
    {
        $body = json_encode($parameters);
        $response = $this->client->request('v2/assets/' . $assetId . '/metadata', 'PATCH', $body);

        if (!$response) {
            $this->messages[] = 'Could not set metadata';
            return false;
        }

        return true;
    }

    /**
     * Get performance analytics
     * @param string $assetId
     * @param string $dateRange
     * @return mixed
     */
    public function getPerformance($assetId, $dateRange = 'day')
    {
        $date = $this->getDateRange($dateRange);

        $response = $this->client->request('v2/analytics/reports/asset/' . $assetId . '/performance/total/' . $date, 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get performance';
            return false;
        }

        return json_decode($response, true);
    }

    public function getAccountPerformance($dateRange = 'day')
    {
        $date = $this->getDateRange($dateRange);

        $response = $this->client->request('v2/analytics/reports/account/performance/total/' . $date, 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get performance';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get engagement analytics
     * @param string $assetId
     * @param string $dateRange
     * @return mixed
     */
    public function getEngagement($assetId, $dateRange = 'day')
    {
        $date = $this->getDateRange($dateRange);

        $response = $this->client->request('v2/analytics/reports/asset/' . $assetId . '/engagement/total/' . $date, 'GET');

        if (!$response) {
            $this->messages[] = 'Could not get performance';
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get the date range
     * @param string $dateRange
     * @return string
     */
    private function getDateRange($dateRange)
    {
        if ($dateRange == 'day') {
            $date = date('Y-m-d');
        } else if ($dateRange == 'month') {
            $begin = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
            $end = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, 1, date('Y')));
            $date = $begin . '...' . $end;
        } else if ($dateRange == 'week') {
            // TODO
        }

        return $date;
    }

    /**
     * Convert an SRT file to the DFXP string
     * @param string $srt
     * @return mixed
     */
    public function convertSrtToDfxp($srt)
    {
        if (!file_exists($srt)) {
            return false;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $xml .= '<tt xmlns="http://www.w3.org/2006/04/ttaf1" xmlns:tts="http://www.w3.org/2006/04/ttaf1#styling" xml:lang="en">' . PHP_EOL;
        $xml .= '<head>' . PHP_EOL;
        $xml .= '<styling>' . PHP_EOL;
        $xml .= '<style id="default" tts:color="white" tts:backgroundColor="black" />' . PHP_EOL;
        $xml .= '</styling>' . PHP_EOL;
        $xml .= '</head>' . PHP_EOL;
        $xml .= '<body style="default" id="theBody">' . PHP_EOL;
        $xml .= '<div xml:lang="en">' . PHP_EOL;

        $lines = file($srt, FILE_IGNORE_NEW_LINES);

        $captionNumber = 0;
        $timingInformation = '';
        $firstLine = '';
        $secondLine = '';
        $thirdLine = '';

        $counter = 0;
        foreach ($lines as $line) {
            switch ($counter % 6) {
                case 0: // Caption number
                    $captionNumber = $line;
                    break;
                case 1: // Timing information
                    $timingInformation = $line;
                    break;
                case 2: // First line of text
                    $firstLine = trim($line);
                    if (is_numeric($firstLine)) {
                        $firstLine = '';
                    }
                    break;
                case 3: // Second line of text
                    $secondLine = trim($line);
                    if (is_numeric($secondLine)) {
                        $secondLine = '';
                    }
                    break;
                case 4: // Third (last) line of text
                    $thirdLine = trim($line);
                    if (is_numeric($thirdLine)) {
                        $thirdLine = '';
                    }
                    // 00:00:00,000 --> 00:00:04,403
                    list($begin, $end) = explode(' --> ', str_replace(array(',', "\r"), array('.', ''), $timingInformation));
                    $xml .= '<p begin="' . $begin . '" end="' . $end . '">' . $firstLine . '<br />' . $secondLine . '<br />' . $thirdLine . '</p>' . PHP_EOL;
                    break;
                case 5: // Empty line
                    break;
            }

            $counter++;
        }

        $xml .= '</div>' . PHP_EOL;
        $xml .= '</body>'. PHP_EOL;
        $xml .= '</tt>' . PHP_EOL;

        return $xml;
    }

    /**
     *
     * @param int $chunkSize
     * @return int
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
        return $this->chunkSize;
    }

    /**
     *
     * @param string $path
     * @return int
     */
    private function createChunks($path)
    {
        $handle = fopen($path, 'rb');
        $counter = 1;

        while (!feof($handle)) {
            $buffer = fread($handle, $this->chunkSize);
            // Write buffer into chunk file...
            file_put_contents($path . '_' . $counter, $buffer);
            $counter++;
        }

        return $counter;
    }

    /**
     *
     * @param string $path
     * @param int $numberOfChunks
     */
    private function deleteChunks($path, $numberOfChunks)
    {
        for ($i = 1; $i <= $numberOfChunks; $i++) {
            unlink($path . '_' . $i);
        }
    }
}