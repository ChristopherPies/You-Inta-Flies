<?php

class DDM_Video_FromPhotosEvent {

	protected $types = array('iamge', 'fade','background','title','color');

	protected $type;
	protected $data = '';
	protected $duration = 1;
	protected $meta = array();
	protected $subtitle;

	public function __construct( $type, $data, $duration = null, $subtitle = '', $meta = array() ) {

		$this->type = $type;
		$this->data = $data;
		$this->duration = $duration;
		$this->meta = $meta;
		$this->subtitle = '';

	}

	public function toString() {
		$str = '';
		switch ($this->type) {

			case 'image':
				// TODO - use meta to handle fade info that goes on this line - eg kenburns
				$str .= $this->data . ':'. $this->duration . ':'. $this->subtitle;
				if( !empty($this->meta['extrafade']) ) {
					if( is_object( $this->meta['extrafade'] ) ) {
						$str .= ':'. $this->meta['extrafade']->toString();
					} else {
						$str .= ':'. $this->meta['extrafade'];
					}
				}
				break;

			case 'background':
				$str .= $this->type .':'. $this->duration . ':'. $this->subtitle . ':'. $this->data;
				break;
			case 'title':
				$str .= $this->type .':'. $this->duration . ':'. $this->subtitle . ':'. $this->data;
				//$str .= $this->type .':'. $this->data;
				break;

			case 'fade':
				$str .= $this->data .':'. $this->duration . ':';
				break;

			case 'setting':
				$str .= $this->data;
				break;

			default:
				break;
		}

		return $str;
	}

	public function getType() {
		return $this->type;
	}

}