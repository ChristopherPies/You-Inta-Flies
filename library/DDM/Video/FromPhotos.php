<?php

/**
 * D3 2011 - Mark Sticht
 */

require_once('/var/lib/framework/DDM/Video.php');
require_once('/var/lib/framework/DDM/Video/FromPhotosEvent.php');

class DDM_Video_FromPhotos extends DDM_Video {

	protected $defaultImageDuration = 5;
	protected $defaultBackground = 'black';
	protected $maxTitlesPerPage = 3;
	protected $titleDuration = 1.5;
	protected $textColor = 'white';

	protected $outputName = 'fromPhotos';
	protected $audio = null;

	protected $events = array();

	/**
	 * Array of titles to automagicaly add in the video
	 *
	 * @var array
	 */
	protected $titles;

	/**
	 * Set default duration for images to show
	 *
	 * @param float $seconds
	 * @return DDM_Video_FromPhotos
	 */
	public function setDefaultImageDuration( $seconds ) {
		$this->defaultImageDuration = $seconds;
		return $this;
	}

	/**
	 * Set default backgrond (color, or image)
	 *
	 * @param string $bg
	 * @return DDM_Video_FromPhotos
	 */
	public function setDefaultBackground( $bg ) {
		$this->defaultBackground = $bg;
		$this->addBackground( $bg, 1);
		return $this;
	}

	/**
	 * set max titles per page
	 *
	 * @param int $int
	 * @return DDM_Video_FromPhotos
	 */
	public function setMaxTitlesPerPage( $int ) {
		$this->maxTitlesPerPage = $int;
		return $this;
	}

	/**
	 * set default text color (hex or name)
	 *
	 * @param string $color
	 * @return DDM_Video_FromPhotos
	 */
	public function setTextColor( $color ) {
		$this->textColor = $color;
		$this->setTitleColor($this->textColor);
		return $this;
	}

	/**
	 * Set the name to output to (base name, no file type)
	 *
	 * @param string $baseName
	 * @return DDM_Video_FromPhotos
	 */
	public function setOutputName ( $baseName ) {
		$this->outputName = $baseName;
		return $this;
	}

	/**
	 * Set the audio file to include
	 *
	 * @param string $file
	 * @return DDM_Video_FromPhotos
	 */
	public function setAudio( $file ) {
		$this->audio = $file;
		return $this;
	}

	/**
	 * Set the title color (name or hex)
	 *
	 * @param string $color
	 * @return DDM_Video_FromPhotos
	 */
	public function setTitleColor( $color ) {
		$e = new DDM_Video_FromPhotosEvent( 'setting', 'title_font_color="'. $color .'"');
		$this->events[] = $e;
		return $this;
	}

	/**
	 * add a background event (color or image)
	 *
	 * @param string $bg
	 * @param float $duration
	 * @param string $subtitle
	 * @return DDM_Video_FromPhotos
	 */
	public function addBackground( $bg, $duration = 1, $subtitle = null ) {
		$e = new DDM_Video_FromPhotosEvent( 'background', $bg, $duration, $subtitle );
		$this->events[] = $e;
		return $this;
	}

	/**
	 * Add a fadein event
	 *
	 * @param float $duration
	 * @return DDM_Video_FromPhotos
	 */
	public function fadein( $duration ) {
		$e = new DDM_Video_FromPhotosEvent( 'fade', 'fadein', $duration );
		$this->events[] = $e;
		return $this;
	}

	/**
	 * Add a crossfade event
	 *
	 * @param float $duration
	 * @return DDM_Video_FromPhotos
	 */
	public function crossfade( $duration ) {
		$e = new DDM_Video_FromPhotosEvent( 'fade', 'crossfade', $duration );
		$this->events[] = $e;
		return $this;
	}

	/**
	 * Add a fadeout event
	 *
	 * @param float $duration
	 * @return DDM_Video_FromPhotos
	 */
	public function fadeout( $duration ) {
		$e = new DDM_Video_FromPhotosEvent( 'fade', 'fadeout', $duration );
		$this->events[] = $e;
		return $this;
	}

	/**
	 * add and image
	 *
	 * @param string $file
	 * @param float $duration
	 * @param string $subtitle
	 * @param array $meta
	 * @return DDM_Video_FromPhotos
	 */
	public function addImage( $file, $duration = null, $subtitle = null, $meta = null) {
		if( $duration === null ) {
			$duration = $this->defaultImageDuration;
		}
		if( !empty($meta['fade']) && is_object($meta['fade']) ) {
			$fade = $meta['fade'];
			unset($meta['fade']);
		}
		$e = new DDM_Video_FromPhotosEvent( 'image', $file, $duration, $subtitle, $meta );
		$this->events[] = $e;
		if( $fade ) {
			$this->events[] = $fade;
			$fade = null;
		}
		return $this;
	}

	/**
	 * Add images from an array or directory
	 *
	 * @param array|dir $arrayOrDirectory
	 * @param float $duration
	 * @param string $type
	 * @param array $meta
	 * @return DDM_Video_FromPhotos
	 */
	public function addImages( $arrayOrDirectory, $duration=null, $type = 'jpg', $meta = null) {

		$max = 99;

		$subtitle = '';
		$dir = '';
		if( is_dir($arrayOrDirectory) ) {
			$dir = $arrayOrDirectory . '/';
			$arrayOrDirectory = scandir( $arrayOrDirectory );
		}
		if( count($arrayOrDirectory) ) {
			foreach($arrayOrDirectory as $f) {

				$tmp = pathinfo($f);
				if( isset($tmp['extension']) && $tmp['extension'] == $type ) {
					$this->addImage( $dir . $f, $duration, $subtitle, $meta );
					if( !$max--) {
						break;
					}
				}

			}
		}
		return $this;

	}

	/**
	 * set title data that will automagicaly be inserted.
	 * If you don't like how this does it, you can add titles yourself. This is the lazy way.
	 *
	 * @param array|file $arrayOrFile
	 * @param float $duration
	 * @return int
	 */
	public function setTitleData( $arrayOrFile, $duration = 1.5 ) {
		$this->titleDuration = $duration;
		if( file_exists($arrayOrFile) ) {
			$arrayOrFile = file( $arrayOrFile );
			if( !is_array($arrayOrFile) ) {
				return 0;
			}
		}
		$this->titles = $arrayOrFile;
		return count($this->titles);
	}

	/**
	 * Write the dvd-slideshow instructions
	 *
	 */
	public function writeInstructions() {
		$contents = '';
		if( !count($this->events) ) {

		}

		// if there are titles to distribute through the photos...
		$this->scatterTitles();

		foreach($this->events as $e) {
			$contents .= $e->toString() . "\n";
		}
		// write the file
		//echo $contents;

		$f = fopen("/tmp/". $this->outputName . '.txt', 'w' );
		fwrite($f, $contents);
		fclose($f);

	}

	/**
	 * Render the slideshow
	 * @param boolean $lowQuality
	 * @param boolean $onlyShowCommand
	 * @return string
	 */
	public function render( $lowQuality = false, $onlyShowCommand = false ) {
		$command = 'dvd-slideshow -n "'. $this->outputName . '" -f /tmp/'. $this->outputName . '.txt';
		if( $this->audio ) {
			$command .= ' -a ' . $this->audio;
		}
		if( $lowQuality ) {
			$command .= ' -L ';
		}

		if( $onlyShowCommand ) {
			echo $command;
			return $this->outputName . '.vob';
		}

		exec($command);
		return $this->outputName . '.vob';
	}

	/**
	 * Automagicaly throw titles in the middle of the photos - for lazy people
	 *
	 * @return void
	 */
	protected function scatterTitles() {

		$titleCount = count($this->titles);
		$titlePageCount = $this->getTitlePageCount();

		if( !count($titleCount) ) {
			return true;
		}

		$imageCount = 0;
		foreach($this->events as $e) {
			if( $e->getType() == 'image') {
				$imageCount++;
			}
		}

		$imagesBetweenTitlePages = floor($imageCount / ($titlePageCount + 1));

		//echo $titlePageCount ."\n\n";

		$page = 1;
		$finalEvents = array();
		$addEvents = array();
		$ii = 1;
		foreach($this->events as $e) {

			if( count($addEvents) && $e->getType() != 'fade' ) {
				$finalEvents = array_merge($finalEvents, $addEvents );
				$addEvents = array();
			}

			// push each event on
			$finalEvents[] = $e;

			if( $e->getType() == 'image' ) {
				if( ($ii -1) == $imagesBetweenTitlePages && $page <= $titlePageCount ) {
					// the title gets added after the image, just save them in  a var
					$bg = new DDM_Video_FromPhotosEvent('background', $this->defaultBackground, 0);
					$finalEvents[] = $bg;
					$addEvents = $this->makeTitleEvents( $page++ );
					//print_r($addEvents); exit;
					$ii = 0;
				}
				$ii++;
			}



		}

		$this->events = $finalEvents;

	}

	/**
	 * Calculate how many title pages there will be
	 *
	 * @return int
	 */
	protected function getTitlePageCount() {
		if( !count($this->titles) ) {
			return 0;
		}
		$ret =  ceil( count($this->titles) / $this->maxTitlesPerPage );
		return $ret;
	}

	/**
	 * Get a group of tiltle texts and make it into an array of events
	 *
	 * @param int $page
	 * @return array
	 */
	protected function makeTitleEvents( $page ) {

		$titlePageCount = $this->getTitlePageCount();
		$start = $this->maxTitlesPerPage * $page;
		$titles = array_slice( $this->titles, $start, $this->maxTitlesPerPage );
		$events = array();
		//$events[] = new DDM_Video_FromPhotosEvent( 'fade', 'crossfade', 1 );
		$titleCount = count($titles);
		$i = 0;
		$lines = array();
		while($i < $titleCount ) {

			$line = '';
			$j = 0;
			foreach($titles as $t) {

				if( $line == '' ) {
					$line .= trim($t);
				} else {
					$line .= '\n' . trim($t);
				}
				$j++;

				// how many extra lines should there be?
				$diff = ($titleCount - $j);
				//echo "$i $j added line $diff \n";

				if( $j > $i ) {
					if( $diff > 0 ) {
						$newlinePadding = str_repeat('\n', $diff );
						$line .= $newlinePadding;
						$diff = 0;
					}
					break;
				}

			}

			if( $diff > 0 ) {
				$newlinePadding = str_repeat('\n', $diff );
				$line .= $newlinePadding;
			}

			$lines[] = $line;

			$i++;
		}

		$duration = $this->titleDuration;
		$notLast = count($lines) - 1;
		foreach($lines as $l) {
			if( !$notLast-- ) {
				$duration *= 2;
			}
			$events[] = new DDM_Video_FromPhotosEvent('title', $l , $duration );
			$events[] = new DDM_Video_FromPhotosEvent( 'fade', 'crossfade', 1 );
			$i++;
		}

		return $events;



	}



}