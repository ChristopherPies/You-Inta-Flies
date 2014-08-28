<?php
class DDM_Mail extends Zend_Mail {

	protected $templateData = array();
	protected $templateName = null;
	protected $templateHeader = null;
	protected $templateFooter = null;
	protected $htmlBody;
	protected $htmlCharset;
	protected $htmlEncoding;
	protected $view;

	public function __construct($charset = null)
	{
	    // Default email charset to UTF-8
	    parent::__construct($charset ? $charset : 'utf-8');
	}

	 /**
     * Prepare the data/template and let the partent send
     *
     * @param Zend_Mail_Transport_Abstract $transport
     * @return DDM_Mail
    */
	public function send( $transport = null) {

		// the easy case, not template - just pass the data on and send

		$this->view = Zend_Registry::get('view');

		if( $this->templateName === null ) {
			parent::setBodyHtml( $this->htmlBody, $this->htmlCharset, $this->htmlEncoding );
			//return parent::send();
		}

		$application = new Zend_Application(
    		APPLICATION_ENV,
    		APPLICATION_PATH . '/configs/application.ini'
		);
		$config = $application->getOptions();
		$emailConfig = $config['email'];

        // default to the From in the config
        if( $this->getFrom() == '' && isset($config['email']['from']) != '' ) {
        	$this->setFrom( $config['email']['from'] );
        }

        // default to the Subject in the config
        if( $this->getSubject() == '' && isset($config['email']['subject']) && $config['email']['subject'] != '' ) {
        	$this->setSubject( $config['email']['subject'] );
        }

        if( isset($config['email']['vars']) && count($config['email']['vars'] ) ) {
        	$tmp = $this->templateData;
        	$this->clearData();
        	$this->setData( $config['email']['vars'] );
        	// clobber the default data from the config with what we were given
        	if( count($tmp) > 0 ) {
        		$this->setData( $tmp );
        	}
        }

        $viewSuffix = $emailConfig['view_suffix'];

        // if running from a cron, the base path isn't set.
        $this->view->addScriptPath(APPLICATION_PATH . '/views/scripts/');

        // assign data for templates
        $data = array();
        foreach ($this->templateData as $key => $value) {
            //$writer->assign($key, $value);
            $data[$key] = $value;
        }

        // subject
        $subject = $this->view->partial( 'email/'. $this->templateName . '-subj.'. $viewSuffix, $data);

        // body text version
        $text = $this->view->partial( 'email/'. $this->templateName . '-text.'. $viewSuffix, $data);

        // body html version
        $html = $this->view->partial( 'email/'. $this->templateName . '-html.'. $viewSuffix, $data);

        // header Text - if set
        if( $this->templateHeader != null ) {
        	$headerText = $this->view->partial( 'email/'. $this->templateHeader . '-text.'. $viewSuffix, $data);
        	$text = $headerText . $text;
		}

		// header Html - if set
        if( $this->templateHeader != null ) {
        	$headerHtml = $this->view->partial( 'email/'. $this->templateHeader . '-html.'. $viewSuffix, $data);
        	$html = $headerHtml . $html;
		}

		// footer Text
        if( $this->templateHeader != null && $text != '' ) {
        	$headerText = $this->view->partial( 'email/'. $this->templateFooter . '-text.'. $viewSuffix, $data);
        	$text .= $headerText;
		}

		// footer Html
        if( $this->templateHeader != null && $html != '' ) {
        	$footerHtml = $this->view->partial( 'email/'. $this->templateFooter . '-html.'. $viewSuffix, $data);
        	$html .= $footerHtml;
		}

		// in case plain text was missing, attempt to convert the html to text
        if( $text == '' && $html != '' ) {
        	$text = strip_tags($html);
        }

        if($this->getSubject()){
        	$this->clearSubject();
            $this->setSubject($subject);
        }
        $this->setBodyText($text);
        if( $html != '' ) {
        	$this->setBodyHtml($html);
        }

        parent::send($transport);

	}

	/**
	 * Add values to the template data array
	 *
	 * @param array $keysValues
	 */
	public function setData( $keysValues ) {
		$this->templateData = array_merge($this->templateData, $keysValues);
	}

	/**
	 * Reset the template data
	 *
	 */
	public function clearData() {
		$this->templateData = array();
	}

	/**
	 * Set the template
	 *
	 * @param string $name
	 */
	public function setBodyTemplateName( $name ) {
		$this->templateName = $name;
	}

	/**
	 * Set the Header template
	 *
	 * @param string $name
	 */
	public function setHeaderTemplateName( $name ) {
		$this->templateHeader = $name;
	}

	/**
	 * Set the Footer template
	 *
	 * @param string $name
	 */
	public function setFooterTemplateName( $name ) {
		$this->templateFooter = $name;
	}

	/**
	 * Set From (after clearing it)
	 *
	 * @param string $from
	 */
	public function setFrom( $email, $name = null) {
		$this->clearFrom();
		parent::setFrom( $email, $name);
	}

	/**
	 * Attaches a file to the email
	 *
	 * @param string $path_to_file
	 * @param string $file_name
	 * @param string $disposition
	 * @param string $encoding
	 *
	 * @return void
	 */
	public function attachFile( $path_to_file, $file_name = null, $disposition = Zend_Mime::DISPOSITION_INLINE, $encoding = Zend_Mime::ENCODING_BASE64 ) {
		if(!file_exists($path_to_file)) {
			return false;
		}

		$binary_string = file_get_contents( $path_to_file);
		if(is_null($file_name)) {
			$file_name = basename($path_to_file);
		}

		$attachment = new Zend_Mime_Part($binary_string);
		$attachment->type = mime_content_type($path_to_file);
		$attachment->disposition = $disposition;
		$attachment->encoding = $encoding;
		$attachment->filename = $file_name;

		$result = $this->addAttachment($attachment);
	}
}
