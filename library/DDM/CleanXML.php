<?php
/**
 * Convert UTF-8 and improperly encoded strings to entities
 * @author whitingj
 *
 */
class DDM_CleanXML {

    /**
     * Clean the string
     * @param string $text
     * @return string
     */
    public function clean($text) {
        return $this->entityCleanup($this->utf8tohtml($text, false));
    }

    /**
     * Converts utf8 string to ascii string with utf8 entities
     * @param string $utf8
     * @param bool $encodeTags
     * @return string
     */
    protected function utf8tohtml($utf8, $encodeTags) {
        //Posted here: http://php.net/manual/en/function.htmlentities.ph
        $result = '';
        for ($i = 0; $i < strlen($utf8); $i++) {
            $char = $utf8[$i];
            $ascii = ord($char);
            if ($ascii < 128) {
                // one-byte character
                $result .= ($encodeTags) ? htmlentities($char) : $char;
            } else if ($ascii < 192) {
                // non-utf8 character or not a start byte
            } else if ($ascii < 224) {
                // two-byte character
                $result .= htmlentities(substr($utf8, $i, 2), ENT_QUOTES, 'UTF-8');
                $i++;
            } else if ($ascii < 240) {
                // three-byte character
                $ascii1 = ord($utf8[$i+1]);
                $ascii2 = ord($utf8[$i+2]);
                $unicode = (15 & $ascii) * 4096 +
                           (63 & $ascii1) * 64 +
                           (63 & $ascii2);
                $result .= "&#$unicode;";
                $i += 2;
            } else if ($ascii < 248) {
                // four-byte character
                $ascii1 = ord($utf8[$i+1]);
                $ascii2 = ord($utf8[$i+2]);
                $ascii3 = ord($utf8[$i+3]);
                $unicode = (15 & $ascii) * 262144 +
                           (63 & $ascii1) * 4096 +
                           (63 & $ascii2) * 64 +
                           (63 & $ascii3);
                $result .= "&#$unicode;";
                $i += 3;
            }
        }
        //Kill any remaining non ascii chars.
        $result = preg_replace('/[^(\x9-\x7F)]*/','', $result);
        return (string)$result;
    }

    /**
     * Converts non xml compliant entites to numeric entites
     * @param string $text
     * @return string
     */
    protected function entityCleanup($text) {
        $conversion = array(
            '&quot;' => '&#34;',
            '&amp;' => '&#38;',
            '&lt;' => '&#60;',
            '&gt;' => '&#62;',
            '&nbsp;' => '&#160;',
            '&iexcl;' => '&#161;',
            '&cent;' => '&#162;',
            '&pound;' => '&#163;',
            '&curren;' => '&#164;',
            '&yen;' => '&#165;',
            '&brvbar;' => '&#166;',
            '&sect;' => '&#167;',
            '&uml;' => '&#168;',
            '&copy;' => '&#169;',
            '&ordf;' => '&#170;',
            '&laquo;' => '&#171;',
            '&not;' => '&#172;',
            '&shy;' => '&#173;',
            '&reg;' => '&#174;',
            '&hibar;' => '&#175;',
            '&deg;' => '&#176;',
            '&plusmn;' => '&#177;',
            '&sup1;' => '&#185;',
            '&sup2;' => '&#178;',
            '&sup3;' => '&#179;',
            '&acute;' => '&#180;',
            '&micro;' => '&#181;',
            '&para;' => '&#182;',
            '&middot;' => '&#183;',
            '&cedil;' => '&#184;',
            '&ordm;' => '&#186;',
            '&raquo;' => '&#187;',
            '&frac14;' => '&#188;',
            '&frac12;' => '&#189;',
            '&frac34;' => '&#190;',
            '&iquest;' => '&#191;',
            '&Agrave;' => '&#192;',
            '&Aacute;' => '&#193;',
            '&Acirc;' => '&#194;',
            '&Atilde;' => '&#195;',
            '&Auml;' => '&#196;',
            '&Aring;' => '&#197;',
            '&AElig;' => '&#198;',
            '&Ccedil;' => '&#199;',
            '&Egrave;' => '&#200;',
            '&Eacute;' => '&#201;',
            '&Ecirc;' => '&#202;',
            '&Euml;' => '&#203;',
            '&Igrave;' => '&#204;',
            '&Iacute;' => '&#205;',
            '&Icirc;' => '&#206;',
            '&Iuml;' => '&#207;',
            '&ETH;' => '&#208;',
            '&Ntilde;' => '&#209;',
            '&Ograve;' => '&#210;',
            '&Oacute;' => '&#211;',
            '&Ocirc;' => '&#212;',
            '&Otilde;' => '&#213;',
            '&Ouml;' => '&#214;',
            '&times;' => '&#215',
            '&Oslash;' => '&#216;',
            '&Ugrave;' => '&#217;',
            '&Uacute;' => '&#218;',
            '&Ucirc;' => '&#219;',
            '&Uuml;' => '&#220;',
            '&Yacute;' => '&#221;',
            '&THORN;' => '&#222;',
            '&szlig;' => '&#223;',
            '&agrave;' => '&#224;',
            '&aacute;' => '&#225;',
            '&acirc;' => '&#226;',
            '&atilde;' => '&#227;',
            '&auml;' => '&#228;',
            '&aring;' => '&#229;',
            '&aelig;' => '&#230;',
            '&ccedil;' => '&#231;',
            '&egrave;' => '&#232;',
            '&eacute;' => '&#233;',
            '&ecirc;' => '&#234;',
            '&euml;' => '&#235;',
            '&igrave;' => '&#236;',
            '&iacute;' => '&#237;',
            '&icirc;' => '&#238;',
            '&iuml;' => '&#239;',
            '&eth;' => '&#240;',
            '&ntilde;' => '&#241;',
            '&ograve;' => '&#242;',
            '&oacute;' => '&#243;',
            '&ocirc;' => '&#244;',
            '&otilde;' => '&#245;',
            '&ouml;' => '&#246;',
            '&divide;' => '&#247;',
            '&oslash;' => '&#248;',
            '&ugrave;' => '&#249;',
            '&uacute;' => '&#250;',
            '&ucirc;' => '&#251;',
            '&uuml;' => '&#252;',
            '&yacute;' => '&#253;',
            '&thorn;' => '&#254;',
            '&yuml;' => '&#255;');
        return (string)str_ireplace(array_keys($conversion), array_values($conversion), $text);
    }
}