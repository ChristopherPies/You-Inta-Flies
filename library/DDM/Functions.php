<?php

/**
 * Print_r with pre tags
 *
 * @param mixed $data
 * @param boolean $return
 * @return string
 */
function ppr( $data, $return = false ) {

    $debug = debug_backtrace();
    // if it doesn't look like we are in a web environment, display in a more command line friendly way...
    if(!isset($_SERVER['HTTP_HOST'])) {
        $str = print_r($data, true) . "\nCalled From: ". $debug[0]['file'] .' line '. $debug[0]['line'] ."\n\n";

    // web - wrap in pre
    } else {
        $str = '<pre>' . print_r($data, true) . '<br>Called From: '. $debug[0]['file'] .' line '. $debug[0]['line'] .'</pre>';
    }
	if( $return ) {
		return $str;
	}
	echo $str;
}

/**
 * Get the current transport (http or https)
 *
 * @return http | https
 */
function getCurrentTransport() {
	if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' ) {
		return 'https';
	}
	return 'http';
}

/**
 * replace special chars with normal or html
 *
 * @param string $string
 * @return string
 */
function cleanSpecialChars( $string) {

	// zero width space to nothing. What is it good for?
	$string = str_replace("&#65279;", '', $string);

	// 2nd and 3rd params ensure that the decode doesn't drop things like &Agrave; or &aacute;
	$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

	$encoding = mb_detect_encoding($string);
	//echo "Clean - ". mb_detect_encoding($string) . "<BR>";
	if( $encoding != 'ASCII' && $encoding != '' ) {
		$string = mb_convert_encoding($string, 'HTML-ENTITIES', $encoding);
	}

	//Jared's Cleaner
	// 11/2012 - commented out b/c it has never been in use because of the typo stirng instead of string.
	//$cleaner = new DDM_CleanXML();
	//$stirng = $cleaner->clean($string);

	return $string;
}

/**
 * Strip tag & body
 *
 * @param string $subject
 * @param array $types
 * @return string
 */
function strip_tag_and_contents( $subject, $types = null ) {
    // don't try to handle non-scalars or bools
    if(!is_scalar($subject) || is_bool($subject)) {
        return $subject;
    }
    $defaults = array('style','script','form');
    if($types === null) {
        $types = $defaults;
    }
    if( !count($types) ) {
        return $subject;
    }
    foreach($types as $type) {
        $subject = preg_replace("/<". $type .".*?(\/>|.*?<\/". $type .">)/ims",'',$subject);
    }

    return $subject;
}

/**
 * Clean and fix paragraphs
 *
 * @param string $body
 * @param string $allowedHtml
 * @param boolean $closePtoBR
 * @param boolean $replaceNbspWithSpace
 * @param boolean $stripAttribsInTags
 * @return string
 */
function crap2html( $body, $allowedHtml = '<b><i>', $closePtoBR = true, $replaceNbspWithSpace = true, $stripAttribsInTags = true ) {

	// get rid of half the crap that word sends
    $body = preg_replace("/<!--.*?-->/m", '', $body);

    // strong to b, em to i
    $body = preg_replace("/<strong>/i","<b>",$body);
    $body = preg_replace("/<\/strong>/i","</b>",$body);
    $body = preg_replace("/<em>/i","<i>",$body);
    $body = preg_replace("/<\/em>/i","</i>",$body);

    if($closePtoBR){
        // close p to double break
        $body = preg_replace("/<\/p>/i","<br><br>",$body);
    }

	$body = trim( cleanSpecialChars( $body ) );
	// cleanSpecialChars, unencodes stuff, but we need the non-tag angles to be encoded.
        if(!defined('ENT_HTML401'))
        {
            define('ENT_HTML401',0);
        }
	$body = htmlentitiesOutsideHTMLTags($body, ENT_QUOTES | ENT_HTML401);

	// make sure our allowed tags don't have attributes
	$tags = explode('><', $allowedHtml);

	$tags[0] = str_replace('<','',$tags[0]);
	$tags[count($tags)-1] = str_replace('>','',$tags[count($tags)-1]);
        if($stripAttribsInTags) {
            foreach($tags as $tag ) {
                    if( $tag == 'a') {
                            // TODO, instead of skipping this, make it strip style="", but not href=""
                            continue;
                    }
                    $body = preg_replace("/<{$tag} .*?>/im","<$tag>",$body);
            }
        }

	// nuke style and scripts and other tags that enclose content that a user may paste in
	$body = strip_tag_and_contents( $body, array('style','form','script') );

	// nobreak to real space, tabs too
	if($replaceNbspWithSpace){
	    $body = str_replace('&nbsp;', ' ', $body);
	}
	$body = str_replace("\t", ' ', $body);

	$body = preg_replace("/<br.*?>/im","\n",$body);

	$body = trim(strip_tags($body, $allowedHtml));
	$cleaned = $body;

	$para = explode("\n", trim($body));

	// tacks lines together when pasted from a word-wrapped source
    if( count($para) ) {
            $tmp = '';
            $canHasNew = false;
            foreach( $para as $p ) {
                    $p = trim($p);
                    if( $p ) {
                            // tack each sentence together
                            $tmp .= " ". $p;
                            $canHasNew = true;
                    } elseif( !$p && $canHasNew ) {
                            // we last put a sentence on, so allow a newline
                            $tmp = $tmp . "\n\n";
                            $canHasNew = false;
                    }
            }
            $body = $tmp;
    }

    // check for paragraph sanity
    // if there is only one sentance per paragraph then the above paragraph code didn't work for this case
    $para = explode("\n", trim($body));
    $sane = true;
    $sentCount = 0;
    if( $paraCount = count($para) ) {
    	foreach($para as $key => $p) {
    		if( $key % 1 ) {
    			if( trim($p) ) {
    				// this "paragraph should be empty"
    				$sane = false;
    			}
    		} else {
    			$sents = splitSentance($p);
    			$sentCount += count($sents);
    		}
    	}
    	$avgSentPerPara = $sentCount / $paraCount;
    	if( $avgSentPerPara <= 1 || $paraCount == 1) {
			$sane = false;
    	}
    }

    if( !$sane ) {
    	// the line wrap method didn't work, try another
    	// Keeps a single return just fine, but causes uglier problems for pasted content.
		$body = trim(preg_replace('/\n{3,}/m',"\n\n", $cleaned));
    }

	// we have newlines in the body, but want to save <br> so the WYSIWYG is happy.
	$body = nl2br(trim($body));

	// go from 1 to 2, but not 2 to more. not always needed, but whatever...
	$body = preg_replace("/<br.*?>/im","<br><br>",$body);
	$body = preg_replace("/<br>\s+<br>/ims","<br><br>",$body);
	while( strpos($body, '<br><br><br>') !== false ) {
		$body = str_replace("<br><br><br>","<br><br>",$body);
	}
    // collapse spaces
    while( strpos($body, '  ') !== false ) {
		$body = str_replace("  "," ",$body);
	}

	//echo "<hr>Final<br><br>$body"; exit;

	return $body;

}

/**
 * Convert double quotes to curly (left/right) quotes.
 * Assumes quotes in input are html entities, returns same
 * Only suitable for a paragraph at a time so pairs can be better matched
 * @param string $str
 * @return string
 */
function smartQuoteString($str) {
    preg_match_all('/(&quot;)/', $str, $matches,  PREG_OFFSET_CAPTURE);
    if(!empty($matches[0])) {
    	$count = count($matches[0]);
    	$isEven = (boolean)!($count % 2);
    	if($isEven) {
        	$replaceWith = array('&ldquo;','&rdquo;');
        	$offset = 0;
            foreach($matches[0] as $key => $match) {
        	   $replaceIndex = $key % 2;
               //echo "\n<BR>Replace {$match[0]} with {$replaceWith[$replaceIndex]}. Length = " . strlen($match[0]);
        	   $str = substr_replace($str,$replaceWith[$replaceIndex], $match[1] + $offset, strlen($match[0]));
        	   // as we replace a 6 char entity with a 7 char entity, the positions from the preg_search are off by the difference
        	   $offset += (strlen($replaceWith[$replaceIndex]) - strlen($match[0]));
        	}
    	}
    }
    return $str;
}

/**
 * Encode entities, but not those that make up tags
 * @param unknown $htmlText
 * @param unknown $ent
 * @return string|mixed
 */
function htmlentitiesOutsideHTMLTags ($htmlText, $ent) {
	if($htmlText == '') {
		return '';
	}

	$arr =  str_split($htmlText,1);

	$oCount = 0;
	$cCount = 0;
	$posLastOpen = null;
	for($i = 0; $i < count($arr); $i++) {
		$setPosLastOpen = null;
		$c = $arr[$i];
		if($c == '<') {
			$setPosLastOpen = $i;
			$oCount++;
		}
		if($c == '>') {
			$cCount++;
		}
		// ther are 2 more opens than close <
		if(($oCount -2) == $cCount) {
			$new = str_split('&lt;',1);
			$i += 3;
			$setPosLastOpen = $i;
			array_splice($arr, $posLastOpen, 1, $new);
			$oCount--;
		}
		if($setPosLastOpen != null) {
			$posLastOpen = $setPosLastOpen;
		}
	}

	$htmlText = join('', $arr);

    $matches = Array();
    $sep = '###HTMLTAG###';

    preg_match_all(":</{0,1}[a-z]+[^>]*>:i", $htmlText, $matches);

    $tmp = preg_replace(":</{0,1}[a-z]+[^>]*>:i", $sep, $htmlText);
    $tmp = explode($sep, $tmp);

    for ($i=0; $i < count($tmp); $i++) {
        $tmp[$i] = htmlentities($tmp[$i], $ent, 'UTF-8', false);
    }

    $tmp = join($sep, $tmp);

    for ($i=0; $i < count($matches[0]); $i++) {
        $tmp = preg_replace(":$sep:", $matches[0][$i], $tmp, 1);
    }

    return $tmp;
}

/**
Flesch-Kincade

Periods, explanation points, colons and semicolons serve as sentence delimiters; each group of continuous non-blank characters with beginning and ending punctuation removed counts as a word; each vowel in a word is considered one syllable subject to:
(a) -es, -ed and -e (except -le) endings are ignored;
(b) words of three letters or shorter count as single syllables;
(c) consecutive vowels count as one syllable.

*/

/**
 * Prep text for a Flesch-Kincade score
 *
 * @param string $text
 * @return string
 */
function prepFleschScore( $text ) {
	$text = str_replace('e.g.', 'eg ', $text);
	$text = str_replace('i.e.', 'ie ', $text);
	$text = str_replace('a.m.', 'am ', $text);
	$text = str_replace('p.m.', 'pm ', $text);
	//$text = str_replace('-',' ', $text);
	//$text = str_replace('-',' ', $text);
	return $text;
}


/**
 * Flesch-Kincade Reading Ease score
 *
 * @param string $text
 * @return float
 */
function readingScoreEase( $text ) {

	// Flesch-Kincaid Grade Level Readability
	// http://en.wikipedia.org/wiki/Flesch%E2%80%93Kincaid_readability_test

	$text = prepFleschScore($text);

	$lines = splitSentance($text);
	$wordCount = countWords($text);
	$lineCount = count( $lines );
	$syllableCount = countSyllables( $text, true );

	if( !$lineCount ) {
		return null;
	}

	$avgWordPerLine = $wordCount / $lineCount;
	$avgSyllPerWord = $syllableCount / $wordCount;

	$score = 206.835 -  (1.015 * $avgWordPerLine) - (84.6 * $avgSyllPerWord );
/*
	ppr( array(
		'word count' => $wordCount,
		'line count' => $lineCount,
		'syllable count' => $syllableCount,
		'avg. syllables per word' => $avgSyllPerWord,
		'avg words/line' => $avgWordPerLine,
		'ease' => $score
	) );
*/
	return round($score,2);

}

/**
 * Flesch-Kincade Grade level
 *
 * @param string $text
 * @return string
 */
function readingScoreGradeLevel( $text ) {

	// Flesch-Kincaid Grade Level Readability
	// http://en.wikipedia.org/wiki/Flesch%E2%80%93Kincaid_readability_test
	// http://www.standards-schmandards.com/exhibits/rix/index.php

	$text = prepFleschScore($text);

	$lines = splitSentance($text);

	$wordCount = countWords($text);
	$lineCount = count( $lines );
	$syllableCount = countSyllables( $text, true );

	if( !$lineCount ) {
		return null;
	}

	$avgWordPerLine = $wordCount / $lineCount;
	$avgSyllPerWord = $syllableCount / $wordCount;

	// the real deal.
	$score = (0.39 * $avgWordPerLine) + (11.8 * $avgSyllPerWord ) - 15.59;

	// tweaked to match Word a little better. we tend to be 10-20% too high.
	$score = $score * 0.85;

	/*
	ppr( array(
		'word count' => $wordCount,
		'line count' => $lineCount,
		'syllable count' => $syllableCount,
		'avg. syllables per word' => $avgSyllPerWord,
		'avg words/line' => $avgWordPerLine,
		'grade' => $score
	) );
	*/

	return round($score,2);

}

/**
 * Split a text into sentances (based on punctuation
 *
 * @param string $text
 * @return array
 */
function splitSentance( $text ) {

	$text = str_replace('.', '. ', $text);
	$lines = array();

	$text = str_replace('e.g.', 'eg', $text);
	$text = str_replace('i.e.', 'ig', $text);
	$text = str_replace('"', ' ', $text);

	$text = trim($text);
	if( !strlen($text) ) {
		return $lines;
	}

	//$punc = array('.','!','?',';',':');
	$punc = array('.','!','?',';');
	$rs = str_replace( $punc, ".", $text );
	$tmpLines = explode( '.', $rs );

	if( count($tmpLines) ) {
		foreach($tmpLines as $l) {
			$l = trim($l);
			if( strlen($l) > 1 ) {
				$lines[] = $l;
			}
		}
	} else {
		$lines[] = $text;
	}

	//ppr($lines); exit;
	return $lines;

}

/**
 * Split a string into words
 *
 * @param string $text
 * @return array
 */
function splitWords( $text ) {
	// make 12.10 into 1210
	$clean = preg_replace('/(\d+)\.(\d+)/i', '$1$2', $text);
        //replace the utf 8 decoded &nbsp; with a space
        $clean = str_replace("\xc2\xa0"," ",$clean);
	$clean = preg_replace('([^a-z0-9\s]+)i', '', $clean);
        $clean = preg_replace('/\s/', ' ', $clean);
	$words = explode( ' ', $clean);
	return $words;
}

/**
 * Count words in a string
 *
 * @param string $text
 * @return int
 */
function countWords( $text ) {
	// str_word_count() doesn't do a great job

	$words = splitWords( $text );
	$wordCount = 0;
	foreach($words as $word) {
		if( trim($word) ) {
			$wordCount++;
		}
	}
	return $wordCount;
}

/**
 * Approximate count of syllables in a word or sentance
 *
 * alien should return 3, apple should return 2, scratched should return 1, ability
 * rugged is a two syllable word that can be made one syllable by adding letters to it to make shrugged
 *
 * @param string $word
 * @param boolean $isSentance
 * @return int
 */
function countSyllables( $word, $isSentance = false ) {

	// clean
	$word = strip_tags( $word );

	if( $word == 'youll' || $word == 'you') {
		return 1;
	}

	// break a sentance up and check each word, return the total
	if( $isSentance ) {
		$words = splitWords($word);
		$total = 0;
		foreach($words as $word) {
			trim($word);
			if( !$word ) {
				continue;
			}
			$tmp = countSyllables( $word );
			//echo "$word $tmp<BR>";
			$total += $tmp;
		}
		return $total;
	}

	if( is_numeric($word) ) {
		return strlen($word);
	}

	$word = str_replace('ly', 'l', $word);

	// check a single word
	$vowels = array('a','e','i','o','u','y');

	$length = strlen($word);
	$i = 0;
	$count = 0;
	$prevVowelCount = 0;
	$conConCount = 0; // consecutive consonants

	// are there any two syllable words that are two letters?
	if( $length < 3 ) {
		return 1;
	}

	// general case, each vowel is a syllable, but two in a row count as one
	$prevVowelCount = 0;
	while( $i < $length ) {
		$char = strtolower($word[$i++]);

		//echo "<B>$char</b> ";

		if( in_array($char, $vowels) ) {
			if( $prevVowelCount == 1 ) {
				// last letter was a vowel, ignore this one
				$prevVowelCount++;
			} else if( $prevVowelCount == 2 ) {
				// last two were vowels, they were already counted, but restart the vowel group at 1 and count this one
				$count++;
				$prevVowelCount = 1;
			} else {
				// just a new vowel
				$count++;
				$prevVowelCount = 1;
			}

		} else {
			$prevVowelCount = 0;
		}

	}

	// we'll have to think of some better rules than these. For now it corrects more than it hurts
	$last = substr($word, -1);

	$lastTwo = substr($word, -2);
	if( $last == 'e' && $lastTwo != 'le' && strlen($word) > 4 ) {
		$count--;
	} else if( $last == 'y' && $prevVowelCount < 2 ) {
		$count--;
	} elseif ($lastTwo == 'ed') {
		//$count--;
	} elseif ($lastTwo == 'es') {
		$count--;
	}

	// make sure "pssst" counts as a syllable
	if( $length > 1 && $count == 0 ) {
		$count = 1;
	}

	return $count;

}

/**
 *
 * Takes a docroot relative path and returns a path with the last updated time prepended
 * This timestamp MUST be handled by an apache rewrite rule
 * Path is expected to begin with a /
 * @param string $path
 * @param string $document_root
 * @param boolean $addDomain
 */
function noCacheFile($path, $document_root='', $addDomain = false){
    if(!$document_root){
        $document_root = PROJECT_ROOT . 'public';
    }
    $fullpath = $document_root . $path;
    $stats = stat($fullpath);
    $retVal = $path;

    $prepend = '';
    if($addDomain === true) {
        $prepend = '//' . $_SERVER['HTTP_HOST'];
    }

    if($stats){
        $dirs = preg_split('/\//',$path);
        array_shift($dirs);
        $top_dir = array_shift($dirs);

        $retVal = $prepend . '/'. $top_dir . '/' . $stats['mtime'] . '/'. join('/',$dirs);
    }
    return $retVal;
}

/**
 * Get Iptc (and now XMP too) info from an image
 *
 * @param string $image_path
 * @return array
 */
function getIptcData( $image_path ) {

	$map = array(
		'version' => '2#000', 				# Max 2 octets,binary number
		'title' => '2#005',					# Max 65 octets, non-repeatable,alphanumeric
		'urgency' => '2#010',				# Max 1 octet, non-repeatable,numeric, 1 - High, 8 - Low
		'category' => '2#015',				# Max 3 octets, non-repeatable, alpha
		'sub_category' => '2#020',			# Max 32 octets, repeatable,alphanumeric
		'keywords' => '2#025',				# Max 64 octets, repeatable,alphanumeric
		'instructions' => '2#040',			# Max 256 octets,non-repeatable, alphanumeric
		'creation_dated' => '2#055',		# Max 8 octets,non-repeatable, numeric, YYYYMMDD
		'creation_time' => '2#060',			# Max 11 octets,non-repeatable, numeric+-, HHMMSS(+|-)HHMM
		'program_used' => '2#065',			# Max 32 octets,non-repeatable, alphanumeric
		'author' => '2#080',				#!Max 32 octets, repeatable,alphanumeric
		'position' => '2#085',				#!Max 32 octets, repeatable,alphanumeric
		'city' => '2#090',					# Max 32 octets, non-repeatable,alphanumeric
		'state' => '2#095',					# Max 32 octets, non-repeatable,alphanumeric
		'country' => '2#101',				# Max 64 octets, non-repeatable,alphanumeric
		'transmission_reference' => '2#103',# Max 32 octets,non-repeatable, alphanumeric
		'headline' => '2#105',				# Max 256 octets, non-repeatable,alphanumeric
		'credit' => '2#110',				# Max 32 octets, non-repeatable,alphanumeric
		'source' => '2#115',				# Max 32 octets, non-repeatable,alphanumeric
		'copyright' => '2#116',				# Max 128 octets,non-repeatable, alphanumeric
		'caption' => '2#120',				# Max 2000 octets, non-repeatable,alphanumeric
		'caption_writer' => '2#122'			# Max 32 octets,non-repeatable, alphanumeric
	);

	$info = null;
    $size = getimagesize ( $image_path, $info);
    $result = array();

	if( is_array($info) ) {
		if( empty($info["APP13"]) ) {
			return $result;
		}
		$iptc = iptcparse($info["APP13"]);
		if( count($iptc) ) {
			foreach($map as $k => $v) {
				if( !empty($iptc[$v][0]) ) {
					$result[ $k ] = $iptc[$v][0];
				}
			}
		}
    }

    $xmp = getImageXMP($image_path);

    foreach($xmp as $key => $value) {
        if(!empty($value)) {
            $result[$key] = $value;
        }
    }

    return $result;
}

/**
 * Get XMP info from a file
 * Not sure what is in your file? Try: http://regex.info/exif.cgi
 *
 * @param string $filename
 * @return array
 */
function getImageXMP($filename) {

    $xmp_parsed = array();

    $file = fopen($filename, 'r');
    $source = fread($file, filesize($filename));

    // look for xmp data
    $xmpdata_start = strpos($source,"<x:xmpmeta");
    $xmpdata_end = strpos($source,"</x:xmpmeta>");
    if( !($xmpdata_start + $xmpdata_end) ) {
        return $xmp_parsed;
    }

    // isolate it
    $xmplenght = $xmpdata_end - $xmpdata_start;
    $xmpdata = substr($source, $xmpdata_start, $xmplenght + 12);
    fclose($file);

    // regexes that we'll look for
    $regexps = array(
        array("name" => "copyright", "regexp" => "/<dc:rights>\s*<rdf:Alt>\s*<rdf:li xml:lang=\"x-default\">(.+)<\/rdf:li>\s*<\/rdf:Alt>\s*<\/dc:rights>/"),
        array("name" => "author", "regexp" => "/<dc:creator>\s*<rdf:Seq>\s*<rdf:li>(.+)<\/rdf:li>\s*<\/rdf:Seq>\s*<\/dc:creator>/"),
        array("name" => "title", "regexp" => "/<dc:title>\s*<rdf:Alt>\s*<rdf:li xml:lang=\"x-default\">(.+)<\/rdf:li>\s*<\/rdf:Alt>\s*<\/dc:title>/"),
        array("name" => "description", "regexp" => "/<dc:description>\s*<rdf:Alt>\s*<rdf:li xml:lang=\"x-default\">(.+)<\/rdf:li>\s*<\/rdf:Alt>\s*<\/dc:description>/"),
        array("name" => "camera model", "regexp" => "/tiff:Model=\"(.[^\"]+)\"/"),
        array("name" => "maker", "regexp" => "/tiff:Make=\"(.[^\"]+)\"/"),
        array("name" => "width", "regexp" => "/tiff:ImageWidth=\"(.[^\"]+)\"/"),
        array("name" => "height", "regexp" => "/tiff:ImageLength=\"(.[^\"]+)\"/"),
        array("name" => "exposure time", "regexp" => "/exif:ExposureTime=\"(.[^\"]+)\"/"),
        array("name" => "source", "regexp" => "/photoshop:Source=\"(.[^\"]+)\"/"),
        array("name" => "source2", "regexp" => "/<photoshop:Source>(.*)</"),
        array("name" => "credit", "regexp" => "/photoshop:Credit=\"(.[^\"]+)\"/"),
        array("name" => "credit2", "regexp" => "/<photoshop:Credit>(.*)</"),
        array("name" => "f number", "regexp" => "/exif:FNumber=\"(.[^\"]+)\"/"),
        array("name" => "iso", "regexp" => "/<exif:ISOSpeedRatings>\s*<rdf:Seq>\s*<rdf:li>(.+)<\/rdf:li>\s*<\/rdf:Seq>\s*<\/exif:ISOSpeedRatings>/"),
        array("name" => "focal lenght", "regexp" => "/exif:FocalLength=\"(.[^\"]+)\"/"),
        array("name" => "user comment", "regexp" => "/<exif:UserComment>\s*<rdf:Alt>\s*<rdf:li xml:lang=\"x-default\">(.+)<\/rdf:li>\s*<\/rdf:Alt>\s*<\/exif:UserComment>/"),
        array("name" => "datetime original", "regexp" => "/xmp:CreateDate=\"(.[^\"]+)\"/"),
        array("name" => "lens", "regexp" => "/aux:Lens=\"(.[^\"]+)\"/")
    );

    $xmp_parsed = array();

    foreach($regexps as $key => $k) {
        unset($r);
        preg_match ($k["regexp"], $xmpdata, $r);
        $xmp_item = null;
        if(!empty($r[1])) {
            $xmp_item = $r[1];
        }
        /*
        // I found this code somewhere else - I don't think we should eval data from a user uplaoded file!
        // I'm not going to bother to fix it - when someone cares about focal length they can...
        if(in_array($k['name'], array('f number', 'focal lenght'))) {
            //eval("\$xmp_item = ".$xmp_item.";");
        }
        */
        $xmp_parsed[$k["name"]] = str_replace("&#xA;", "\n", $xmp_item);
    }
    // was credit/source in a tag or attribute? Either way we want it in one place
	if(!empty($xmp_parsed['credit2']) && empty($xmp_parsed['credit'])) {
		$xmp_parsed['credit'] = $xmp_parsed['credit2'];
		unset($xmp_parsed['credit2']);
	}
	if(!empty($xmp_parsed['source2']) && empty($xmp_parsed['source'])) {
		$xmp_parsed['source'] = $xmp_parsed['source2'];
		unset($xmp_parsed['source2']);
	}

    return $xmp_parsed;
}

/**
 * Calculate age in a unit that makes sense
 *
 * @param string $datetime
 * @param boolean $timeUntil reverses the age (time until X date rather than time from)
 * @return string
 */
function dateAge( $datetime, $timeUntil = false ) {

    if ($datetime == '0000-00-00 00:00:00' || $datetime == '' || $datetime === null ) {
            return '';
    }
    if($timeUntil) {
        if( is_numeric($datetime) ) {
            $etime = $datetime;
        } else {
            $etime = strtotime($datetime);
        }
        if( $etime < 100000 ) {
            return '';
        }

        $ctime = time();
    }
    else {
        if( is_numeric($datetime) ) {
            $ctime = $datetime;
        } else {
            $ctime = strtotime($datetime);
        }
        if( $ctime < 100000 ) {
            return '';
        }

        $etime = time();
    }

	$ovalue = intval(($etime - $ctime) / 86400);
	$olabel = "Days";
	if ($ovalue == 1) {
		$olabel = "Day";
	}
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 3600);
		if ($ovalue == 1) {
			$olabel = "Hr";
		} else {
			$olabel = "Hrs";
		}
	}
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 60);
		$olabel = "Min";
	}
        if($ovalue < 0){
            $ovalue = 0;
        }

	return number_format($ovalue)." $olabel";
}

/**
 * User friendly format of a future date
 *
 * @param sring $string
 * @return string
 */
function dateDue($string)
{
    if ($string == '0000-00-00 00:00:00') {
        return "NULL";
    }
    if ($string != '') {
        $ctime = strtotime($string);
    } else {
        return;
    }
    if (date("Y-m-d") == date("Y-m-d", $ctime)) {
        $output = date("g:ia", $ctime);
    } else if (date("Y") == date("Y", $ctime)) {
        $output = date("M j", $ctime);
    } else {
        $output = date("m/d/y", $ctime);
    }
    return $output;
}

/**
 * Formats numbers as currency. This is different than money_format()
 * because it formats negative numbers properly.
 *
 * @param int $n            The number to format
 * @param int $precision    The number of decimal digits of precision to use
 */
function currency_format($n, $precision = 0)
{
    $fnum = number_format($n, $precision);

    return $n >= 0 ? sprintf('$%s', $fnum) : sprintf('-$%s', abs($fnum));
}

/**
 * Returns two arrays joined on a key, similar to what a SQL FULL JOIN does.
 *
 * @param   array   $arr1
 * @param   array   $arr2   Any duplicate keys in this array will overwrite the ones in the first array
 * @param   string  $key    The key to join the arrays on.
 **/
function array_join($arr1, $arr2, $key)
{
    $keys = array_unique(array_merge(array_keys($arr1[0]), array_keys($arr2[0])));
    $blank_arr = array_combine($keys, array_fill(0, count($keys), null));

    $result = array();
    for($i = 0; $i < count($arr1); $i++)
    {
        if(isset($arr1[$i]))
        {
            $reskey1 = $arr1[$i][$key];
            if(!isset($result[$reskey1]))
            {
                $result[$reskey1] = $blank_arr;
            }
            $result[$reskey1] = array_merge($result[$reskey1], $arr1[$i]);
        }

        if(isset($arr2[$i]))
        {
            $reskey2 = $arr2[$i][$key];
            if(!isset($result[$reskey2]))
            {
                $result[$reskey2] = $blank_arr;
            }
            $result[$reskey2] = array_merge($result[$reskey2], $arr2[$i]);
        }
    }

    return array_values($result);
}

/**
 * Convert coordinate parts from exif data
 *
 * @param array $coordPart
 * @return float
 */
function gps2Num($coordPart) {
    $parts = explode('/', $coordPart);

    if (count($parts) <= 0) {
        return 0;
    }

    if (count($parts) == 1) {
        return $parts[0];
    }

    return floatval($parts[0]) / floatval($parts[1]);
}

/**
 * Convert gps exif data from coordinate to float
 *
 * @param array $exifCoord
 * @return float
 */
function gps2float($exifCoord) {
	$degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
	$minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
	$seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

	$dotdegree = $minutes / 60;
	$seconds = $seconds / 60 /60;

	$dotdegree += $seconds;
	$decimal = $degrees + $dotdegree;
	$decimal = number_format($decimal, 6, '.', '');
	return (float)$decimal;
}

/**
*
* @param string $string
* @return string|void $end
*/
function duration($string, $end = null)
{
    if ($string == '0000-00-00 00:00:00') {
        return "NULL";
    }
    if ($string != '') {
        $ctime = strtotime($string);
    } else {
        return;
    }
  if (null === $end) {
      $original = date_default_timezone_get();
      try {
        $timezone = Zend_Registry::get('timezone');
    } catch(Exception $e) {
        $timezone = date_default_timezone_get();
    }
    date_default_timezone_set($timezone);
    $etime = time();
    date_default_timezone_set($original);
  } else {
    $etime = strtotime($end);
  }
    // Year
	$ovalue = intval(($etime - $ctime) / 31536000);
    $olabel = "Year";

	// Month
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 2635200);
		$olabel = "Month";
	}

	// Weeks
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 604800);
		$olabel = "Week";
	}

	// Day
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 86400);
		$olabel = "Day";
	}

	// Hr
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 3600);
		$olabel = "Hr";
	}

	// Min
	if ($ovalue < 1) {
		$ovalue = intval(($etime - $ctime) / 60);
		$olabel = "Min";
	}

	// fix above labels to be plural if needed, so far all work with just adding an s
	if ($ovalue > 1) {
		$olabel .= "s";
	}

	return number_format($ovalue)." $olabel";
}

/**
 * Check password strength
 * @param string $password
 * @param array $bannedWords
 * @param int $minLength
 * @param int $minStrength
 * @param int $midStrength
 * @return array score and result = (short,not allowed,poor,weak,good)
 */
function getPasswordStrength($password, $bannedWords = array(), $minLength=8, $minStrength=40, $midStrength=56) {

	$symbolSize = 0;

	if( strlen( $password ) < $minLength ) {
		return array('score' => 0, 'result' => 'short');
	}

	$lowerPass = strtolower($password);
	$banned = array_merge(array('password'), $bannedWords);
	foreach($banned as $b) {
            if(!empty($b)) {
                if(strpos($lowerPass, strtolower($b)) !== false ) {
                    return array('score' => 0, 'result' => 'not allowed');
                }
            }
	}

	$classCount = 0;
	if( preg_match( '/[0-9]/', $password ) ) {
	    $symbolSize += 10;
	    $classCount++;
	}
	if( preg_match( '/[a-z]/', $password ) ) {
		$symbolSize += 26;
		$classCount++;
	}
	if( preg_match( '/[A-Z]/', $password ) ) {
		$symbolSize += 26;
		$classCount++;
	}
	if( preg_match( '/[^a-zA-Z0-9]/', $password ) ) {
		$symbolSize += 31;
		$classCount += 2;
	}

	// if they only use one character class, they need to make it longer
	$length = strlen( $password );
	if($classCount == 1) {
	    $length -= 4;
	}

	// make the score a reasonable number
	$natLog = log( pow( $symbolSize, $length ) );
	$score = $natLog / log( 2 );

	// return a final judgement
	if ( $score < $minStrength ) {
		return array('score' => $score, 'result' => 'poor');
	}
	if ( $score < $midStrength ) {
		return array('score' => $score, 'result' => 'weak');
	}
	return array('score' => $score, 'result' => 'good');
}

/**
* String To Class
*
* Purpose:  format text as a class name by removing all non-class tag characters
*           and converting it to lower case
* Input:
*          - string: input text
*
* @author Andrew Bunker <abunker at deseretdigital com>
* @param string $string
* @return string |void
*/
function stringToClass($string)
{
    $string = strtolower($string);
    $string = preg_replace("/[^a-z0-9_-]/", "", $string);

    return $string;
}
/**
 * Is this var something that is serialized?
 * @param mixed $data
 * @return boolean
 */
function isSerialized($data) {
    return @unserialize($data) !== false || $data == 'b:0;';
}

/**
 * Truncate a string on word boundary, append $end if too long
 * @param string $string
 * @param int $maxLength
 * @param string $end
 * @return string
 */
function truncateWords($string, $maxLength, $end = '...') {
    if (strlen($string) > $maxLength) {
        $maxLength = $maxLength - strlen($end);
        $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $maxLength)) . $end;
    }
    return $string;
}

/**
 *
 * @param string $haystack
 * @param string $needle
 * @param boolean $caseSensitive
 * @return boolean
 */
function startsWith($haystack, $needle, $caseSensitive = true) {
    if($caseSensitive) {
        return !strncmp($haystack, $needle, strlen($needle));
    } else {
        return !strncasecmp($haystack, $needle, strlen($needle));
    }
}

/**
 *
 * @param string $haystack
 * @param string $needle
 * @param boolean $caseSensitive
 * @return boolean
 */
function endsWith($haystack, $needle, $caseSensitive = true) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    if($caseSensitive) {
        return (substr($haystack, -$length) === $needle);
    } else  {
        return (substr(strtolower($haystack), -$length) === strtolower($needle));
    }
}

/**
 * http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
 *
 * @param string $hex
 * @return array
 */
function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

/**
 * http://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
 * 500+ is good reading contrast
 *
 * @param int $R1
 * @param int $G1
 * @param int $B1
 * @param int $R2
 * @param int $G2
 * @param int $B2
 * @return int
 */
function coldiff($R1,$G1,$B1,$R2,$G2,$B2){
    return max($R1,$R2) - min($R1,$R2) +
           max($G1,$G2) - min($G1,$G2) +
           max($B1,$B2) - min($B1,$B2);
}

/**
 * Conversion of smarty function for pagination for DDM's old list helper
 *
 * @param int $currentPage
 * @param int $totalPages
 * @param string $baseURI
 * @param string $class
 * @param string $onclick
 * @return string
 */
function page_numbers($currentPage, $totalPages, $baseURI, $class=null, $onclick=null)
{
    $html = '';
    $numToShow = 15;

    $start = ($start = $currentPage - floor($numToShow / 2)) > 0 ? $start : 1;
    $end = ($end = $start + $numToShow) > $totalPages ? $totalPages + 1 : $end; // exclusive
    if($end - $start != $numToShow) {
        $start = ($start = $end - $numToShow) > 0 ? $start : 1;
    }

    $html .= '<ul class="' . $class . '">';
    if ($currentPage != 1) {
        $prev = ($currentPage - 1);
        $anchor = (isset($onclick)) ? '<a href="#" onclick="' . $onclick . '(' . $prev . ');">' : '<a href="' . $baseURI . "/page/{$prev}" . '">';
        $html .= '<li>' . $anchor . '&lt;&#160;Previous' . "</a></li>\n";
    }
    for($i = $start; $i < $end; $i++)
    {
        if($i == $start && $i != 1) {
            $num = 1;
        }
        elseif($i == $end - 1 && $i != $totalPages) {
            $num = $totalPages;
            $html .= "<li>...</li>\n";
        }
        else {
            $num = $i;
        }

        if($i == $start + 1 && $i > 2) {
            $html .= "<li>...</li>\n";
        }
        else {
            $selected = $num == $currentPage ? ' class="selected"' : '';
            $anchor = (isset($onclick)) ? '<a href="#" onclick="' . $onclick . '(' . $num . ');">' : '<a href="' . $baseURI . "/page/{$num}" . '">';
            $html .= '<li' . $selected . '>' . $anchor . $num . "</a></li>\n";
        }
    }
    if ($currentPage != $totalPages) {
        $next = ($currentPage + 1);
        $anchor = (isset($onclick)) ? '<a href="#" onclick="' . $onclick . '(' . $next . ');">' : '<a href="' . $baseURI . "/page/{$next}" . '">';
        $html .= '<li>' . $anchor . 'Next&#160;&gt;' . "</a></li>\n";
    }
    $html .= '</ul>';

    return $html;
}

/**
 * A proper querystring parsing function. Fixes 2 PHP issues:
 *
 * 1) PHP converts characters like '.' and ' ' to '_' when parsing querystrings.
 * 2) PHP doesn't handle 'foo=val1&foo=val2' properly. It lets the last clobber the first instead of creating an array.
 *
 * @param  string $qs The querystring to parse, without the leading '?'
 * @return array      The parsed querystring as an associative array
 */
function parse_qs($qs) {
    $parsed_pairs = array();
    $pairs = explode('&', $qs);

    foreach ($pairs as $pair) {
        // Split into name and value
        list($name, $value) = array_pad(explode('=', $pair, 2), 2, '');

        // This is the correct way to do it, but unfortunately it breaks a lot of our functionality that depends
        // on querystring variables without an equal sign still being an empty string instead of `null`
        //list($name, $value) = array_pad(explode('=', $pair, 2), 2, null);

        preg_match('/(.*)\[(.*)\]/', $name, $matches);
        $usekey = false;
        if (!empty($matches)) {
            $name = $matches[1];
            $key = $matches[2];
            if (!isset($parsed_pairs[$name])) {
                $parsed_pairs[$name] = array();
            }
            if ($key) {
                $usekey = true;
            }
        }

        if (isset($parsed_pairs[$name])) {
            // Stick multiple values into an array
            if (is_array($parsed_pairs[$name])) {
                if ($usekey) {
                    $parsed_pairs[$name][$key] = $value;
                } else {
                    $parsed_pairs[$name][] = $value;
                }
            } else {
                $parsed_pairs[$name] = array($parsed_pairs[$name], $value);
            }
        } else {
            $parsed_pairs[$name] = $value;
        }
    }

    return $parsed_pairs;
}

/**
 * Sometimes we have trouble json encoding data because the strings in our nested data structures aren't properly encoded. This fixes that.
 *
 * @param  array $array  The array to encode
 * @return array         An array with proper UTF-8 encoded values
 */
function utf8EncodeArray($array) {

    foreach($array as $key => $value) {
        if(is_object($value) && $value instanceof  Zend_Db_Table_Rowset) {
            $value = (array) $value;
        }

        if(is_array($value)) {
            $array[$key] = utf8EncodeArray($value);
        } elseif(is_string($value) && mb_detect_encoding($value) != 'UTF-8') {
            $array[$key] = utf8_encode($value);
        }
    }
    return $array;
}

/**
 * Shrinks a number to fit approx 4 or less spaces using 4.4k for 4400, etc. with some rounding
 *
 * @param int $number
 * @return string
 * @throws Exception
 */
function shrink_number($number) {
    $number = intval($number);
    if($number >= 1000000000000) {
        throw new Exception('Such a large number is not yet supported, but you can if you want to.');
    } else if($number >= 1000000000) {
        $label = 'g';
        if($number > 100000000000) {
            $precision = 0;
        } else if($number > 10000000000) {
            $precision = 1;
        } else {
            $precision = 2;
        }
        return round($number/1000000000, $precision).$label;
    } else if($number >= 1000000) {
        $label = 'm';
        if($number > 100000000) {
            $precision = 0;
        } else if($number > 10000000) {
            $precision = 1;
        } else {
            $precision = 2;
        }
        return round($number/1000000, $precision).$label;
    } else if($number >= 1000) {
        $label = 'k';
        if($number > 100000) {
            $precision = 0;
        } else if($number > 10000) {
            $precision = 1;
        } else {
            $precision = 2;
        }
        return round($number/1000, $precision).$label;
    } else {
        return $number;
    }
}

/**
 * Computes the case insensitive, word order independent similarity of 2 strings
 * after removing punctuation, html, and converting all whitespace and dashes to a single space
 *
 * @param string $a
 * @param string $b
 * @param array $articlesToEliminate - an array of words to ignore in the comparison with spaces before and after each word
 * @return float - 0 indicates no similarity, 1 indicates exact match
 */
function cosineSimilarity($a, $b, $weighProperNouns = 10, $ignoreCapitalizedFirstWordsInSentences = true, $articlesToEliminate =
        array(' a ', ' a lot ', ' about ', ' across ', ' after ', ' again ',
    ' against ', ' all ', ' almost ', ' am ', ' among ', ' an ', ' and ', ' any ', ' as ', ' at ', ' be ', ' because ',
    ' before ', ' between ', ' but ', ' by ', ' come ', ' do ', ' down ', ' east ', ' enough ', ' even ', ' ever ',
    ' every ', ' far ', ' few ', ' five ', ' for ', ' forward ', ' four ', ' from ', ' get ', ' give ', ' go ', ' had ',
    ' half ', ' have ', ' he ', ' her ', ' here ', ' hers ', ' him ', ' his ', ' how ', ' i ', ' if ', ' in ', ' is ',
    ' it ', ' its ', ' keep ', ' let ', ' lets ', ' little ', ' make ', ' many ', ' may ', ' much ', ' my ', ' near ',
    ' nine ', ' no ', ' north ', ' not ', ' now ', ' of ', ' off ', ' on ', ' one ', ' only ', ' or ', ' other ', ' our ',
    ' out ', ' over ', ' please ', ' put ', ' quite ', ' say ', ' see ', ' seem ', ' send ', ' seven ', ' she ', ' six ',
    ' so ', ' some ', ' south ', ' still ', ' such ', ' take ', ' ten ', ' than ', ' that ', ' the ', ' their ', ' them ',
    ' then ', ' there ', ' they ', ' this ', ' those ', ' though ', ' three ', ' through ', ' till ', ' to ', ' together ',
    ' tomorrow ', ' two ', ' under ', ' up ', ' us ', ' very ', ' was ', ' we ', ' well ', ' were ', ' west ', ' when  ',
    ' where ', ' while ', ' who ', ' why ', ' will ', ' with ', ' yes ', ' yesterday ', ' you ', ' your ')) {

    /**
     * Dot product
     * a・b = summation{i=1,n}(a[i] * b[i])
     *
     * @param array $a
     * @param array $b
     * @return mixed
     */
    $dotProduct = function(array $a, array $b) {
        $dotProduct = 0;
        // to speed up the process, use keys with non-empty values
        $keysA = array_keys(array_filter($a));
        $keysB = array_keys(array_filter($b));
        $uniqueKeys = array_unique(array_merge($keysA, $keysB));
        foreach ($uniqueKeys as $key) {
            if (!empty($a[$key]) && !empty($b[$key])) {
                $dotProduct += ($a[$key] * $b[$key]);
            }
        }
        return $dotProduct;
    };

    /**
     * Euclidean norm
     * ||x|| = sqrt(x・x) // ・ is a dot product
     *
     * @param array $vector
     * @return mixed
     */
    $norm = function(array $vector) use($dotProduct){
        return sqrt($dotProduct($vector, $vector));
    };

    /**
     * Cosine similarity for non-normalised vectors
     * sim(a, b) = (a・b) / (||a|| * ||b||)
     *
     * @param array $a
     * @param array $b
     * @return mixed
     */
    $cosinus = function(array $a, array $b) use($norm, $dotProduct){
        $normA = $norm($a);
        $normB = $norm($b);
        return (($normA * $normB) != 0)
               ? $dotProduct($a, $b) / ($normA * $normB)
               : 0;
    };
    $a = str_replace('-', ' ',strip_tags(html_entity_decode($a, ENT_QUOTES)));
    $b = str_replace('-', ' ',strip_tags(html_entity_decode($b, ENT_QUOTES)));
    $a = preg_replace('/\s+/', ' ',$a);
    $b = preg_replace('/\s+/', ' ',$b);
    if($ignoreCapitalizedFirstWordsInSentences) {
        $a = preg_replace_callback('/([\.|?|!|;|:])([ ])+([A-Z]){1}/', function($matches){
            return $matches[1].$matches[2].strtolower($matches[3]);
        }, strtolower(substr($a, 0, 1)).substr($a, 1));
        $b = preg_replace_callback('/([\.|?|!|;|:])([ ])+([A-Z]){1}/', function($matches){
            return $matches[1].$matches[2].strtolower($matches[3]);
        }, strtolower(substr($b, 0, 1)).substr($b, 1));
    }
    $a = preg_replace('/[^a-zA-Z0-9 ]/', '', $a);
    $b = preg_replace('/[^a-zA-Z0-9 ]/', '', $b);
    $a = str_ireplace($articlesToEliminate, ' ', $a);
    $b = str_ireplace($articlesToEliminate, ' ', $b);

    $aWords = str_word_count($a, 1, '0123456789');
    $bWords = str_word_count($b, 1, '0123456789');
    $allWords = array();
    foreach($aWords as $word) {
        if(!in_array($word, $allWords)) {
            $allWords []= $word;
        }
    }
    foreach($bWords as $word) {
        if(!in_array($word, $allWords)) {
            $allWords []= $word;
        }
    }
    $aOccurances = array();
    $aCounts = array_count_values($aWords);
    foreach($aCounts as $word=>&$count) {
        if(substr($word, 0, 1) == strtoupper(substr($word, 0, 1))) {
            $count *= $weighProperNouns;
        }
    }
    unset($count);
    foreach($allWords as $word) {
        $aOccurances[$word] = isset($aCounts[$word]) ? $aCounts[$word] : 0;
    }
    $bOccurances = array();
    $bCounts = array_count_values($bWords);
    foreach($bCounts as $word=>&$count) {
        if(substr($word, 0, 1) == strtoupper(substr($word, 0, 1))) {
            $count *= $weighProperNouns;
        }
    }
    unset($count);
    foreach($allWords as $word) {
        $bOccurances[$word] = isset($bCounts[$word]) ? $bCounts[$word] : 0;
    }
    return $cosinus($aOccurances, $bOccurances);
}

/**
 * Function to convert file extensions to mime types
 *
 * @param  (string)  $ext  File extension to map to mimetype
 * @return (string)  mimetype or file extension if mimetype could not be found
 */
function extension_to_mimetype($ext) {
    static $mtMap = array();

    $ext = strtolower($ext);
    if(empty($mtMap)) {
        $mtfile = file('/etc/mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach($mtfile as $line) {
            if($line[0] == '#') continue;
            $line = preg_split("/\s+/", $line, 2);
            if(count($line) > 1) {
                foreach(explode(' ', $line[1]) as $mtExt) {
                    $mtMap[$mtExt] = $line[0];
                }
            }
        }
    }

    return @$mtMap[$ext] ?: $ext;
}

function mimetype_to_extension($mime) {
    static $extMap = array();

    $mime = strtolower($mime);
    if(empty($extMap)) {
        $mtfile = file('/etc/mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach($mtfile as $line) {
            if($line[0] == '#') continue;
            $line = preg_split("/\s+/", $line, 2);
            if(count($line) > 1) {
                foreach(explode(' ', $line[1]) as $mtExt) {
                    // Prefer shortest extension
                    if(!isset($extMap[$line[0]]) || strlen($mtExt) < strlen($extMap[$line[0]])) {
                        $extMap[$line[0]] = $mtExt;
                    }
                }
            }
        }

        $extMap['application/illustrator'] = 'ai';
    }

    return @$extMap[$mime] ?: $mime;
}

/**
 * Looks at the Zend Registry for a timezone and converts the time from the default set timezone
 * to the registry timezone in the format specified.
 *
 * Will read any date string that strtotime would read, in addition to timestamps.
 *
 * @param mixed $dateStringOrTimestamp
 * @param string $format
 * @return string
 */
function getTimezonedDate($dateStringOrTimestamp = null, $format = 'M j, Y g:i a', $appendTimezoneName = true) {
    try {
        $timezone = Zend_Registry::get('timezone');
    } catch(Exception $e) {
        $timezone = date_default_timezone_get();
    }
    if(is_numeric($dateStringOrTimestamp)) {
        $dateStringOrTimestamp = date('Y-m-d H:i:s', $dateStringOrTimestamp);
    }
    if($dateStringOrTimestamp instanceof DateTime) {
        $dateStringOrTimestamp = $dateStringOrTimestamp->format('Y-m-d H:i:s');
    }
    $DT = new DateTime($dateStringOrTimestamp, new DateTimeZone(date_default_timezone_get()));
    $DT->setTimeZone(new DateTimeZone($timezone));
    $formattedDate = $DT->format($format);
    if($appendTimezoneName) {
        $formattedDate .= ' '.$DT->format('T');
    }
    return $formattedDate;
}

/**
 * Like PHP's `realpath()` but for paths that don't exist but still need to be normalized
 *
 * @param  string $path      The path to normalize
 * @param  string $separator Path component separator. Defaults to '/'.
 * @return string
 */
function normalize_path($path, $separator = '/') {
    $parts = explode($separator, $path);
    $newPath = [];
    foreach($parts as $part) {
        if($part == '' || $part == '.') continue;
        if($part == '..')
            array_pop($newPath);
        else
            $newPath[] = $part;
    }

    return (substr($path, 0, 1) == $separator ? $separator : '') . implode($separator, $newPath);
}