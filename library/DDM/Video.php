<?php

class DDM_Video {

	/**
	 * Convert a vob to mp4
	 *
	 * @param string $file
	 * @return $string
	 */
	public function vobToMp4( $file ) {

		$base = str_replace( '.vob', '', $file );
		$command = "ffmpeg -y -i $file -r 30000/1001 -b 2M -bt 4M -vcodec libx264 -pass 2 -vpre hq -acodec libfaac -ac 2 -ar 48000 -ab 192k $base.mp4";
		exec($command);
		return "$base.mp4";

	}

	/**
	 * Interleave a file
	 *
	 * @param string $file
	 */
	public function interleave( $file ) {
		$command = "MP4Box -inter 500 $file";
		$exec($command);
	}

	/**
	 * Rotate a video 90, -90 or 180 degrees
	 *
	 * @param string $file
	 * @param int $degrees
	 * @return boolean
	 */
	public function rotate($file, $degrees) {

		if($degrees == 0 || $degrees == 'none') {
			return true;
		}

		$allowedDegrees = array('1' => 90, '2' => -90, '11' => 180);
		$transform = null;
		foreach($allowedDegrees as $t => $d) {
			if($d == $degrees) {
				$transform = $t;
			}
		}

		$tmp = $file . '_rotate_'. $degrees;
		$result = null;

		switch ($transform) {
			case 1:
			case 2:

				$cmd = "mencoder -ovc lavc -vf rotate=$transform -oac pcm $file -o $tmp";
				//echo $cmd . "\n";
				exec($cmd, $result);
				break;

			case 11: // rotate one twice
				$tmp2 = $tmp . '-2';
				$cmd = "mencoder -ovc lavc -vf rotate=1 -oac pcm $file -o $tmp2";
				//echo "cmd 1 " . $cmd . "\n";
				exec($cmd, $result);
				$cmd = "mencoder -ovc lavc -vf rotate=1 -oac pcm $tmp2 -o $tmp";
				//echo "cmd 2 " . $cmd . "\n";
				exec($cmd, $result);
				//echo "rotated 180";
				break;

			default:
				return false;
				break;
		}

		if(filesize($tmp)) {
			//echo "mv $tmp to $file ";
			$cmd = "mv $tmp $file";
			exec($cmd, $result);
			return true;
		}

		return false;

	}

	/**
	 * get meta data from a video file
	 *
	 * @param string $file
	 * @return array
	 */
	public function parseMetadata($file) {
		$meta = array();
		$type = mime_content_type($file);
		$specificMeta = array();

		switch ($type) {

			// QUICKTIME
			case 'video/quicktime':

				$cmd = "mp4info $file";
				exec($cmd, $lines);
				if(count($lines) > 1) {
					unset($lines[0]);
					unset($lines[1]);
					unset($lines[2]);
				}

				if(count($lines)) {
					foreach($lines as $line) {
						$parts = preg_split('/\t/', $line);
						$specificMeta[$parts[1]] = $parts[2];
					}
				}

				if(isset($specificMeta['video'])) {

					// resolution
					preg_match('/(\d)+x(\d)+/', $specificMeta['video'], $matches);
					if(isset($matches[0]) && strpos($matches[0], 'x') !== false) {
						$specificMeta['resolution'] = $matches[0];
						$res = preg_split('/x/', $specificMeta['resolution']);
						if(count($res) == 2) {
							$specificMeta['width'] = $res[0];
							$specificMeta['height'] = $res[1];
						}
					}

					// length
					preg_match('/(\d)\.(\d)+/', $specificMeta['video'], $matches);
					if(!empty($matches[0])) {
						$specificMeta['length'] = $matches[0];
					}

					//print_r($matches); exit;
				}


				$qt = $this->parseQuicktimeMetadata($file);
				$specificMeta = array_merge($specificMeta, $qt);
				break;

			// QUICKTIME
			case 'video/3gpp':

				/*
				$cmd = "mplayer -identify -frames 0 $file";
				exec($cmd, $lines);

				$map = array(
					'ID_VIDEO_WIDTH' => 'width',
					'ID_VIDEO_HEIGHT' => 'height',
					'ID_LENGTH' => 'length'
				);

				if(count($lines)) {
					foreach($lines as $line) {

						if(count($map)) {
							foreach($map as $k => $v) {
								if(strpos($line, $k) == 0 ) {
									echo $line . "\n";
									$specificMeta[$v] = trim(str_replace($k.'=', '', $line));
									unset($map[$k]);
								}
							}
						}
					}
				}
				*/

				$cmd = "ffprobe $file 2>&1";
				exec($cmd, $lines);

				$info = join($lines);

				preg_match('/(\d){2,5}x(\d){2,5}/', $info, $matches);
				if(isset($matches[0]) && strpos($matches[0], 'x') !== false) {
					$specificMeta['resolution'] = $matches[0];
					$res = preg_split('/x/', $specificMeta['resolution']);
					if(count($res) == 2) {
						$specificMeta['width'] = $res[0];
						$specificMeta['height'] = $res[1];
					}
				}

				preg_match('/creation_time(\s)+:(\s)+(\d){4}-(\d){2}-(\d){2} (\d){2}:(\d){2}:(\d){2}/', $info, $matches);
				if(isset($matches[0])) {
					preg_match('/(\d){4}-(\d){2}-(\d){2} (\d){2}:(\d){2}:(\d){2}/', $matches[0], $matches);
					if(isset($matches[0])) {
						$specificMeta['create_datetime'] = $matches[0];
						/*
						$y = date('Y', strtotime($specificMeta['create_datetime']));
						if($y < 1950) {
							$y += 66;
							$specificMeta['create_datetime'] = date("$y-m-d H:i:s", strtotime($specificMeta['create_datetime']));
						}
						*/
						$specificMeta['create_time'] = date("H:i:s", strtotime($specificMeta['create_datetime']));
						$specificMeta['create_date'] = date("Y-m-d", strtotime($specificMeta['create_datetime']));
					}
				}
				//print_r($matches); exit;

				//creation_time   : 1946-05-14 23:33:53


				if(count($lines)) {
					foreach($lines as $line) {
						//echo "$line \n";
					}
				}

				break;

			default:

				break;
		}

		$meta = array_merge($meta, $specificMeta);

		return $meta;
	}

	/**
	 * Parse metadata from quicktime (iphone4)
	 *
	 * @param string $file
	 * @return array
	 */
	public function parseQuicktimeMetadata( $file ) {

		$rotations = array(
			'180' => 'ff ff 00 00 00 00 00 00  02 38 00 00 01 40 00 00',
			'90' => '00 00 00 00 00 00 00 00  01 10 00 00 00 00 00 00',
			'-90' => '01 00 00 00 00 00 00 00  00 00 00 00 01 e0 00 00',
			'none' => '00 01 00 00 00 00 00 00  00 00 00 00 00 00 00 00');


		/*
		// use mp4 utility to dump info, look at second matrix (first never matches)

		// 1 foo.mov (rotated right, vol button on top right) 180 rotation needed
		ff ff 00 00 00 00 00 00 02 38 00 00 01 40 00 00

		// 4 test.mov (normal position) 90 right rotation needed
		00 00 00 00 00 00 00 00 01 10 00 00 00 00 00 00

		// 3 upside.mov (vertical - upside down) 90 left rotation needed
		00 00 00 00 00 00 00 00 00 00 00 00 01 e0 00 00

		// 2 left (vol button on bottom) no rotation needed
		00 01 00 00 00 00 00 00 00 00 00 00 00 00 00 00
		*/

		// mpeg4ip-utils - mp4info mp4dump
		// ffmpeg -i {file}
		// http://stackoverflow.com/questions/2208522/ffmpeg-on-iphone-modifying-video-orientation

		/*
		Logs using 4 iPhone 4 videos with the normal cam:
			(1) landscape cam on right side (home button on left)
			(2) landscape left
			(3) portrait upside-down
			(4) portrait up-right (home button at bottom)
		*/

		/*
		?6%�ވ�lZx'�����N���X	Y��
		meta"hdlrmdta~keys-mdtacom.apple.quicktime.camera.identifierAmdtacom.apple.quicktime.camera.framereadouttimeinmicroseconds@ilstdata�Backdata}R�udta�mak�Apple�swr�5.1$�day�2012-04-02T21:53:31-0600'�xyz�+40.3459-111.9680+1497.571/�mo�iPhone 4�meta"hdlrmdta�keys mdtacom.apple.quicktime.make(mdtacom.apple.quicktime.creationdate,mdtacom.apple.quicktime.location.ISO6709$mdtacom.apple.quicktime.software!mdtacom.apple.quicktime.model�ilstdataUS�Apple0(dataUS�2012-04-02T21:53:31-06003+dataUS�+40.3459-111.9680+1497.571/dataUS�5.1 dataUS�iPhone 4
		*/

		/*��
   /��	l(stco?=Z �����Q^�
   		meta"hdlrmdta~keys-mdtacom.apple.quicktime.camera.identifierAmdtacom.apple.quicktime.camera.framereadouttimeinmicroseconds@ilstdata�Backdata}R�udta�mak�Apple�swr�5.1$�day�2012-04-03T22:20:57-0600'�xyz�+40.3463-111.9672+1489.301/�mo�iPhone 4�meta"hdlrmdta�keys mdtacom.apple.quicktime.make(mdtacom.apple.quicktime.creationdate,mdtacom.apple.quicktime.location.ISO6709$mdtacom.apple.quicktime.software!mdtacom.apple.quicktime.model�ilstdataUS�Apple0(dataUS�2012-04-03T22:20:57-06003+dataUS�+40.3463-111.9672+1489.301/dataUS�5.1 dataUS�iPhone 4

		/*
		$cmd = 'ffmpeg -i '. $file;
		echo $cmd;
		exec($cmd, $info);
		ppr($info);
		exit;
		*/

		$result = array('lat' => null, 'lon' => 'null', 'date' => null, 'time' => null, 'date_time' => null);
		$line = null;
		$cmd = 'tail -n 1 ' . $file;
		exec($cmd, $line);

		if(empty($line[0])) {
			return array();
		} else {
			$meta = trim($line[0]);
		}

		/* Date/Time */
		// dayÇ2012-04-02T21:53:31-0600
		$day = null;
		$datePos = strpos($meta, 'day');
		$dateString = substr($meta, $datePos + 7, 27);
		preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateString, $matches);
		$time = null;
		if(count($matches)) {
			$date = $matches[0];
			preg_match('/T(\d{2}):(\d{2}):(\d{2})/', $dateString, $matches);
			$time = str_replace('T', '', $matches[0]);
			$time = strtotime($date . ' '. $time);
		}
		if($time > 10000) {
			$result['date'] = date('Y-m-d', $time);
			$result['time'] = date('H:i:s', $time);
			$result['date_time'] = date('Y-m-d H:i:s', $time);
		}

		/* Location */
		// xyz�+40.3459-111.9680+1497.571/
		// is +1497.571 the elevation in meters?
		$location = null;
		$locationPos = strpos($meta, 'xyz');
		$locationString = trim(substr($meta, $locationPos + 7, 28));
		$locationString = str_replace('+', ' ', $locationString);
		preg_match('/(-?\d{1,3}\.\d{1,6})(-?\d{1,3}\.\d{1,6}).*$/', $locationString,$matches);
		if(count($matches) == 3) {
			$result['lat'] = $matches[1];
			$result['lon'] = $matches[2];
		}

		/* Rotation */
		$dump = null;
		/*
		mp4dump is gone from ubuntu
		$cmd = "mp4dump $file";
		exec($cmd, $dump);

		$str = join($dump);
		$parts = preg_split('/matrix/', $str);
		foreach($parts as $p) {
			if(strpos($p, '40 00 00 00') != false) {
				$matrix = trim(str_replace("    ", ' ', substr($p, 15, 100)));
				//echo "\n\n". $matrix ."\n\n";
				foreach($rotations as $key => $m) {
					if($m == $matrix) {
						$result['rotate'] = $key;
						//echo "\n\n". $matrix ."\n\n";
						break;
					}
				}
			}
		}
		*/

		// using mp4file instead of mp4dump
		$cmd = "mp4file --dump  $file | grep -a4 matrix | grep 00000010";
		exec($cmd, $dump);

		$dump = join($dump);
		foreach($rotations as $key => $m) {
			if(strpos($dump, $m) !== false) {
				$result['rotate'] = $key;
				//echo "\n\n". $matrix ."\n\n";
				break;
			}
		}

		// unset keys if value is empty
		foreach($result as $key => $value) {
			if($value == '' || $value === null || $value == 'null') {
				//echo "unset $key \n";
				unset($result[$key]);
			}
		}

		return $result;
	}

}