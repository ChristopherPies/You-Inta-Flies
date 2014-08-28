<?php

/**
 * TODO add all status codes
 * currently only supports client and server errors
 *
 */
class DDM_Http_Status {
    // Client Error
    const BAD_REQUEST                   = 400;
    const UNAUTHORIZED                  = 401;
    const PAYMENT_REQUIRED              = 402;
    const FORBIDDEN                     = 403;
    const NOT_FOUND                     = 404;
    const METHOD_NOT_ALLOWED            = 405;
    const NOT_ACCEPTABLE                = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT               = 408;
    const CONFILCT                      = 409;
    const GONE                          = 410;
    const LENGTH_REQUIRED               = 411;
    const PRECONDITION_FAILED           = 412;
    const REQUEST_ENTTITY_TOO_LARGE     = 413;
    const REQUEST_URI_TOO_LONG          = 414;
    const UNSUPPORTED_MEDIA_TYPE        = 415;
    const REQUEST_RANGE_NOT_SATISFIABLE = 416;
    const EXPECTATION_FAILED            = 417;
    const UNPROCCESSABLE_ENTITY         = 422;

    // Server Error
    const INTERNAL_SERVER_ERROR         = 500;
    const NOT_IMPLEMENTED               = 501;
    const BAD_GATEWAY                   = 502;
    const SERVICE_UNAVAILABLE           = 503;
    const GATEWAY_TIMEOUT               = 504;
    const HTTP_VERSION_NOT_SUPPORTED    = 505;
    const BANDWIDTH_LIMIT_EXCEEDED      = 509;

    /**
     * Response code dictionary
     *
     * @var array
     */
    protected static $httpMessages = array(
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

	/**
	 * Converts response code to text
	 *
	 * @param int $code
	 * @return string
	 */
	public static function statusCodeAsText($code)
	{
	    $message = '';
	    if (isset(self::$httpMessages[$code])) {
	        $message = self::$httpMessages[$code];
	    }

	    return $message;
	}
}