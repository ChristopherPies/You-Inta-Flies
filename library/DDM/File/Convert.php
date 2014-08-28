<?php

class DDM_File_Convert {

    /**
     * Create a word doc from html (or?)
     *
     * @param string $inputFile
     * @return mixed
     */
    public static function htmlToDoc($inputFile) {
        return DDM_File_Convert::libreOfficeConvert($inputFile, 'doc');
    }

    /**
     * Create an rtf from html (or?)
     *
     * @param string $inputFile
     * @return mixed
     */
    public static function htmlToRtf($inputFile) {
        return DDM_File_Convert::libreOfficeConvert($inputFile, 'rtf');
    }

    /**
     * Create a PDF from html (or?)
     *
     * @param string $inputFile
     * @return mixed
     */
    public static function htmlToPdf($inputFile) {
        return DDM_File_Convert::libreOfficeConvert($inputFile, 'pdf');
    }

    /**
     * Create a PDF from html (or?)
     *
     * @param string $inputFile
     * @return mixed
     */
    public static function pdfToPng($inputFile, $density=600, $height=1200, $quality = 90) {
        $width = ceil($height / 1.43);
        $outputFile = str_replace('.pdf', '.png', $inputFile);
        $cmd = "/usr/bin/convert -density {$density}x$density -resize {$height}x$width -quality $quality $inputFile $outputFile";
        exec($cmd, $result);
    }

    /**
     * Run libreoffice conversions (html to doc, rtf, pdf)
     *
     * @param unknown_type $inputFile
     * @param unknown_type $outputType
     * @return unknown
     */
    protected static function libreOfficeConvert($inputFile, $outputType) {

        if(!file_exists('/usr/bin/libreoffice')) {
            throwException( new Exception('libreoffice must be installed'));
        }

        $dir = '/tmp';

        if( filesize($inputFile) > 0 ) {
            // the export gives libreoffice a place to write tmp files since www-data doesn't have a home
            $cmd = "export HOME=/tmp;/usr/bin/libreoffice --headless --convert-to $outputType $inputFile -outdir $dir";
            exec($cmd, $result);

            if(isset($result[0])) {
                $output = $result[0];
                $files = preg_split('/->/', $output);
                if(isset($files[1])) {
                    $rightHalf = trim($files[1]);
                    $files = preg_split('/ /', $rightHalf);
                    if(count($files) > 1) {
                        return trim($files[0]);
                    } else {
                        return $rightHalf;
                    }
                }
            }
        }

        return false;

    }


}
