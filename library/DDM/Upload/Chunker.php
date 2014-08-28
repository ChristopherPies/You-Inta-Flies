<?php
include_once 'DDM/Upload/Chunker/ErrorResponse.php';

/**
 * Handles chunked uploads
 *
 */
class DDM_Upload_Chunker
{
    // Debug Flags
    const DEBUG_DYNAMIC                  = 1;

    // Force Failure
    const DEBUG_FORCE_BAD_REQUEST_METHOD = 2;
    const DEBUG_FORCE_NO_CONTENT_LENGHT  = 4;
    const DEBUG_FORCE_CHUNK_TOO_LARGE    = 8;
    const DEBUG_FORCE_CHUNK_NOT_SAVED    = 16;
    const DEBUG_FORCE_ASSEMBLY_FAILED    = 32;

    protected static $_debugCodeMap = array(
        'dynamic'              => self::DEBUG_DYNAMIC,
        'bad_request_method'   => self::DEBUG_FORCE_BAD_REQUEST_METHOD,
        'no_content_lenght'	   => self::DEBUG_FORCE_NO_CONTENT_LENGHT,
        'chunk_too_large'      => self::DEBUG_FORCE_CHUNK_TOO_LARGE,
        'chunk_not_saved'      => self::DEBUG_FORCE_CHUNK_NOT_SAVED,
        'assembly_failed'      => self::DEBUG_FORCE_ASSEMBLY_FAILED
    );

    /**
     * Root path where all uploaded files will be stored
     *
     * @var string
     */
    protected $_rootPath  = null;

    /**
     * Path where temporary chunk files will be stored
     *
     * @var string
     */
    protected $_chunkPath = null;

    /**
     * Upload directory structure based on type
     *
     * @var array
     */
    protected $_typePaths = array(
        'image' => 'media/images'
    );


    /**
     * Stores response vars
     *
     * @var array
     */
    protected $_response = array(
        'status'        => 0,
        'chunkSize'     => 0,
        'chunkFilePath' => null,
        'fileSize'      => 0,
        'filePath'      => null
    );

    /**
     * Stores Error Response
     *
     * @var ErrorResponse
     */
    protected $_errorResponse = null;

    /**
     * Debug mode force the chunker to fail at specific points.
     *
     * @var int
     */
    protected $_debugMode = 0;

    /**
     * Constructor
     *
     * Supported fields for $config
     * -
     * - rootPath   = string
     * - chunkPath  = string
     * - typePaths  = array
     * - debugMode  = boolean
     *
     * @param array $config
     */
    public function __construct($config=null)
    {
        if (! is_null($config)) {
            $this->setOptions($config);
        }

        $this->init();
    }

    /**
     * Getter for debug mode
     *
     * @return boolean
     */
    public function getDebugMode()
    {
        return $this->_debugMode;
    }

	/**
     * Setter for debug mode
     *
     * @param boolean|string|array $mode
     * @param boolean $dynamicEnabled
     */
    public function setDebugMode($mode, $dynamicEnabled=true)
    {
        if (is_string($mode)) {
            $mode = (isset(self::$_debugCodeMap[$mode]))? self::$_debugCodeMap[$mode] : 0;
        } else if (is_array($mode)) {
            $mode = 0;
            foreach ($mode as $debugMode) {
                $mode += (isset(self::$_debugCodeMap[$mode]))? self::$_debugCodeMap[$mode] : 0;
            }
        }

        if ($dynamicEnabled) {
            $mode |= self::DEBUG_DYNAMIC;
        }

        if (! is_null($mode)) {
            $this->_debugMode = $mode;
        }

    }

    /**
     * Getter for root path
     *
     * @return string
     */
    public function getRootPath()
    {
        return $this->_rootPath;
    }

	/**
     * Setter for root path
     *
     * @param string
     */
    public function setRootPath($path)
    {
        $this->_rootPath = $path;
    }

    /**
     * Getter for chunk path
     *
     * @return string
     */
    public function getChunkPath()
    {
        return $this->_chunkPath;
    }

	/**
     * Setter for chunk path
     *
     * @param string
     */
    public function setChunkPath($path)
    {
        $this->_chunkPath = $path;
    }

	/**
     * Getter for type paths
     *
     * @return array
     */
    public function getTypePaths()
    {
        return $this->_typePaths;
    }

	/**
     * Setter for type paths
     *
     * @param string
     */
    public function setTypePaths($paths)
    {
        $this->_typePaths = $paths;
    }

    /**
     * Registers new type path
     *
     * @param string $type
     * @param string $path
     */
    public function registerTypePath($type, $path)
    {
        $this->_typePaths[$type] = $path;
    }

    /**
     * Main method that handles chunk requests
     *
     * required/supported query params:
     * -
     * - uid           = int
     * - chunk-num     = int
     * - total-chunks  = int
     * - extention     = string
     * - type          = string
     * - [debug-mode]  = int|sting
     *
     * @return json response
     */
    public function handle()
    {
        // Currently only supports PUT requests
        if ($_SERVER['REQUEST_METHOD'] != 'PUT' || $this->_debugMode & self::DEBUG_FORCE_BAD_REQUEST_METHOD) {
            $this->_errorResponse->setStatus(DDM_Http_Status::BAD_REQUEST);
            $this->_sendJson($this->_errorResponse);
        }

        // Read Params
        $uid = $this->_getParam('uid');
        $chunkNum = $this->_getParam('chunk-num');
        $totalChunks = $this->_getParam('total-chunks');
        $extension = $this->_getParam('ext');
        $uploadType = $this->_getParam('type', 'image');

        // Check for dynamic debug flags
        if ($this->_debugMode & self::DEBUG_DYNAMIC) {
            $debugMode = $this->_getParam('debug-mode', null);
            $this->setDebugMode($debugMode, true);
        }

        // Validate params
        if (is_null($uid) || is_null($chunkNum) || is_null($totalChunks) || is_null($extension)) {
            $this->_errorResponse->setStatus(DDM_Http_Status::BAD_REQUEST);
            $this->_errorResponse->setMessage('Missing required parameters');
            $this->_sendJson($this->_errorResponse);
        }

        // Validate request
        $contentLength = $this->_getContentLength();
        if (! $contentLength || $this->_debugMode & self::DEBUG_FORCE_NO_CONTENT_LENGHT) {
            $this->_errorResponse->setStatus(DDM_Http_Status::LENGTH_REQUIRED);
            $this->_sendJson($this->_errorResponse);
        }

        $maxFileSize = $this->_parseIniSizeStr(ini_get('upload_max_filesize'));
        if ($contentLength > $maxFileSize || $this->_debugMode & self::DEBUG_FORCE_CHUNK_TOO_LARGE) {
            $this->_errorResponse->setStatus(DDM_Http_Status::REQUEST_ENTTITY_TOO_LARGE);
            $this->_sendJson($this->_errorResponse);
        }

        // Process chunk
        $success = $this->_saveChunk($uid, $chunkNum, $contentLength);
        if (! $success) {
            $this->_errorResponse->setStatus(DDM_Http_Status::INTERNAL_SERVER_ERROR);
            $this->_errorResponse->setMessage('Chunk not saved.');
            $this->_sendJson($this->_errorResponse);
        }

        // Final Chunk processed Assemble upload
        if ($chunkNum >= $totalChunks) {

            $success = $this->_assembleChunks($uid, $extension, $uploadType);
            if (! $success) {
                $this->_errorResponse->setStatus(DDM_Http_Status::INTERNAL_SERVER_ERROR);
                $this->_errorResponse->setMessage('Unable to assemble upload.', 'fileSystem', true);
                $this->_sendJson($this->_errorResponse);
            }
        }

        // Debug mode
        if ($this->_debugMode) {
            header('Debug-Mode: '. $this->_debugMode);
            $data['debugMode'] = $this->_debugMode;
        }

        // Success send response
        $this->_sendJson($this->_response, 202);
    }

    /**
     * Set chunker options
     *
     * Supported fields for $config
     * -
     * - rootPath   = string
     * - chunkPath  = string
     * - typePaths  = array
     * - debugMode  = boolean
     * @param array $config
     */
    public function setOptions($config) {

        if (isset($config['debugMode'])) {
            $this->setDebugMode($config['debugMode']);
        }

        if (isset($config['rootPath'])) {
            $this->setRootPath($config['rootPath']);
        }

        if (isset($config['chunkPath'])) {
            $this->setChunkPath($config['rootPath']);
        }

        if (isset($config['typePaths'])) {
            $this->setTypePaths($config['typePaths']);
        }
    }

    protected function init()
    {
        // Check that root path is set
        if (is_null($this->_rootPath)) {
            $this->_rootPath = './uploads';

            if (! is_dir($this->_rootPath)) {
                throw new Exception('Root path must exist');
            }
        }

        // Check that chunk path is set
        if (is_null($this->_chunkPath)) {
            $this->_chunkPath = $this->_rootPath . '/chunks';
        }

        $this->_errorResponse = new DDM_Upload_Chunker_ErrorResponse();

    }

    /**
     * Stor chunk file
     *
     * @param int $uid
     * @param int $chunkNum
     * @param int $chunkLen
     * @return int|boolean bytes written or false on error
     */
    protected function _saveChunk($uid, $chunkNum, $chunkLen)
    {
        if ($this->_debugMode & self::DEBUG_FORCE_CHUNK_NOT_SAVED) {
            return false;
        }

        // Set up upload dir
        $chunkDir = $this->_getChunkDir($uid);

        $chunkFileName = sprintf('ck_%d_%04d', $uid, $chunkNum);

        // Save file
        $phpInput = fopen('php://input', 'r');
        $chunkFile = fopen($chunkDir . '/' . $chunkFileName, 'w');
        $bytesWritten = stream_copy_to_stream($phpInput, $chunkFile, $chunkLen);
        fclose($chunkFile);
        fclose($phpInput);

        if (! $bytesWritten) {
            return false;
        }

        // Store response
        $this->_response['chunkSize'] = $bytesWritten;
        $this->_response['chunkFilePath'] = $chunkFileName;

        return $bytesWritten;
    }

    /**
     * Assemles uploaded chunks and stores complete file
     *
     * @param int $uid
     * @param string $extension
     * @param string $type
     * @return int|boolean final file size in bytes or false on error
     */
    protected function _assembleChunks($uid, $extension, $type=null)
    {
        $chunkDirPath = $this->_getChunkDir($uid, false);

        // Open chunk directory
        try {
            $chunkDir = new DirectoryIterator($chunkDirPath);
        } catch (Exception $e) {
            $this->_errorResponse->setMessage($e->getMessage());
            return false;
        }

        // Open file handle
        $fileDir = $this->_getFileDir($uid, $type);
        $fileName = $fileDir . '/' . $uid . '.' . ltrim($extension, '.');
        $file = fopen($this->_rootPath . $fileName, 'w');
        if (! $file || $this->_debugMode & self::DEBUG_FORCE_ASSEMBLY_FAILED) {
            return false;
        }

        // Assemble chunks
        foreach($chunkDir as $dirEntry) {
            if (! $dirEntry->isDot()) {
                $entryPath = $dirEntry->getRealPath();
                $chunkFile = fopen($entryPath, 'r');
                stream_copy_to_stream($chunkFile, $file);
                fclose($chunkFile);

                // Clean up
                unlink($entryPath);
            }
        }
        fclose($file);
        rmdir($chunkDirPath);


        // Update reasponse
        $this->_response['status']   = 1;
        $this->_response['fileSize'] = filesize($this->_rootPath . $fileName);
        $this->_response['filePath'] = $fileName;

        return $this->_response['fileSize'];
    }

    /**
     * Attempts to extract query param.
     *
     * @param string $index
     * @param mixed $default
     * @return mixed query param value if it exists $default value otherwise
     */
    protected function _getParam($index, $default=null)
    {
        return (isset($_GET[$index]))? $_GET[$index] : $default;
    }

    /**
     * Returns chunk directory given upload id
     *
     * @param int $uid
     * @param boolean $createDir if chunk directory does not exist it will be created
     * @return string chunk directory
     */
    protected function _getChunkDir($uid, $createDir=true)
    {
        $chunkDir = $this->_chunkPath . '/' . $uid;

        if ($createDir && ! is_dir($chunkDir)) {
            mkdir($chunkDir, 0777, true);
        }

        return $chunkDir;
    }

    /**
     * Returns segmented file directory given upload id and extension
     *
     * @param int $uid
     * @param string $extension
     * @param boolean $createDir
     * @return string file directory
     */
    protected function _getFileDir($uid, $type=null, $createDir=true)
    {
        // Build
        $fileDir = '';

        // Segment by type
        if (! is_null($type) && isset($this->_typePaths[$type])) {
            $fileDir .= '/' . $this->_typePaths[$type];
        }

        // Segment by uid
        $d1 = intval($uid / 10000);
        $d2 = intval($uid / 100) % 100;

        $fileDir .= '/' . $d1 . '/' . $d2;

        if ($createDir && ! is_dir($this->_rootPath . $fileDir)) {
            mkdir($this->_rootPath . $fileDir, 0777, true);
        }

        return $fileDir;

    }


    /**
     * Json encodes $data and sends it with appropriate header
     *
     * @param array|ErrorResponse $data
     */
    protected function _sendJson($data, $responceCode=202) {

        if ($data instanceof DDM_Upload_Chunker_ErrorResponse) {
            $data = $data->toArray();
            $responceCode = $data[DDM_Upload_Chunker_ErrorResponse::STATUS];
        }

        // Send headers
        header('Content-Type: application/json', true, $responceCode);

        // Include debug mode if applicable
        if ($this->_debugMode) {
            $data['debugMode'] = $this->_debugMode;
        }

        // json encode
        echo json_encode($data);
        exit();
    }

    /**
     * Retrieves request content length
     * @return int content length in bytes
     */
    protected function _getContentLength()
    {
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            return $_SERVER['CONTENT_LENGTH'];
        }

        // Look at headers to determine content length
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Content-Length'])) {
            return $requestHeaders['Content-Length'];
        }

        return false;

    }

    /**
     * Reads php ini size and converts it to bytes
     *
     * @param string $sizeStr
     * @return mixed int size in bytes on success false otherwise
     */
    protected function _parseIniSizeStr($sizeStr) {
    	$sizeTable = array(
    		'K'	=> 1024,
    		'M'	=> 1048576,
    		'G'	=> 1073741824
    	);

    	$sizeStr = strtoupper($sizeStr);

    	$parts = array();

    	preg_match('/(\d+)\s*('. implode('|', array_keys($sizeTable)) .'){1}/', $sizeStr, $parts);

    	// Parsing error
    	if (empty($parts) || count($parts) != 3) {
    		return false;
    	}

    	return $parts[1] * $sizeTable[$parts[2]];

    }

}