<?php

namespace Parse;

use Parse\Internal\Encodable;

/**
 * ParseFile - Representation of a Parse File object.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseFile implements Encodable
{
    /**
     * The filename.
     *
     * @var string
     */
    private $name;

    /**
     * The URL of file data stored on Parse.
     *
     * @var string
     */
    private $url;

    /**
     * The data.
     *
     * @var string
     */
    private $data;

    /**
     * The mime type.
     *
     * @var string
     */
    private $mimeType;

    /**
     * Return the data for the file, downloading it if not already present.
     *
     * @throws ParseException
     *
     * @return mixed
     */
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }
        if (!$this->url) {
            throw new ParseException('Cannot retrieve data for unsaved ParseFile.');
        }
        $this->data = $this->download();

        return $this->data;
    }

    /**
     * Return the URL for the file, if saved.
     *
     * @return string|null
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Return the name for the file
     * Upon saving to Parse, the name will change to a unique identifier.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Send a REST request to delete the ParseFile.
     *
     * @throws ParseException
     */
    public function delete()
    {
        if (!$this->url) {
            throw new ParseException('Cannot delete file that has not been saved.');
        }

        $headers = ParseClient::_getRequestHeaders(null, true);
        $url = ParseClient::getAPIUrl().'files/'.$this->getName();
        $rest = curl_init();
        curl_setopt($rest, CURLOPT_URL, $url);
        curl_setopt($rest, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($rest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($rest);
        $contentType = curl_getinfo($rest, CURLINFO_CONTENT_TYPE);
        if (curl_errno($rest)) {
            throw new ParseException(curl_error($rest), curl_errno($rest));
        }
        curl_close($rest);
    }

    /**
     * Return the mimeType for the file, if set.
     *
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Create a Parse File from data
     * i.e. $file = ParseFile::createFromData('hello world!', 'hi.txt');.
     *
     * @param mixed  $contents The file contents
     * @param string $name     The file name on Parse, can be used to detect mimeType
     * @param string $mimeType Optional, The mime-type to use when saving the file
     *
     * @return ParseFile
     */
    public static function createFromData($contents, $name, $mimeType = null)
    {
        $file = new self();
        $file->name = $name;
        $file->mimeType = $mimeType;
        $file->data = $contents;

        return $file;
    }

    /**
     * Create a Parse File from the contents of a local file
     * i.e. $file = ParseFile::createFromFile('/tmp/foo.bar',
     * 'foo.bar');.
     *
     * @param string $path     Path to local file
     * @param string $name     Filename to use on Parse, can be used to detect mimeType
     * @param string $mimeType Optional, The mime-type to use when saving the file
     *
     * @return ParseFile
     */
    public static function createFromFile($path, $name, $mimeType = null)
    {
        $contents = file_get_contents($path, 'rb');

        return static::createFromData($contents, $name, $mimeType);
    }

    /**
     * Internal method used when constructing a Parse File from Parse.
     *
     * @param $name
     * @param $url
     *
     * @return ParseFile
     */
    public static function _createFromServer($name, $url)
    {
        $file = new self();
        $file->name = $name;
        $file->url = $url;

        return $file;
    }

    /**
     * Encode to associative array representation.
     *
     * @return string
     */
    public function _encode()
    {
        return [
            '__type' => 'File',
            'url'    => $this->url,
            'name'   => $this->name,
        ];
    }

    /**
     * Uploads the file contents to Parse, if not saved.
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->url) {
            $response = $this->upload();
            $this->url = $response['url'];
            $this->name = $response['name'];
        }

        return true;
    }

    private function upload()
    {
        $fileParts = explode('.', $this->getName());
        $extension = array_pop($fileParts);
        $mimeType = $this->mimeType ?: $this->getMimeTypeForExtension($extension);

        $headers = ParseClient::_getRequestHeaders(null, false);
        $url = ParseClient::getAPIUrl().'files/'.$this->getName();
        $rest = curl_init();
        curl_setopt($rest, CURLOPT_URL, $url);
        curl_setopt($rest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($rest, CURLOPT_BINARYTRANSFER, 1);
        $headers[] = 'Content-Type: '.$mimeType;
        curl_setopt($rest, CURLOPT_POST, 1);
        curl_setopt($rest, CURLOPT_POSTFIELDS, $this->getData());
        curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($rest);
        $contentType = curl_getinfo($rest, CURLINFO_CONTENT_TYPE);
        if (curl_errno($rest)) {
            throw new ParseException(curl_error($rest), curl_errno($rest));
        }
        curl_close($rest);
        if (strpos($contentType, 'text/html') !== false) {
            throw new ParseException('Bad Request', -1);
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            throw new ParseException(
                $decoded['error'],
                isset($decoded['code']) ? $decoded['code'] : 0
            );
        }

        return $decoded;
    }

    private function download()
    {
        $rest = curl_init();
        curl_setopt($rest, CURLOPT_URL, $this->url);
        curl_setopt($rest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($rest, CURLOPT_BINARYTRANSFER, 1);
        $response = curl_exec($rest);
        if (curl_errno($rest)) {
            throw new ParseException(curl_error($rest), curl_errno($rest));
        }
        $httpStatus = curl_getinfo($rest, CURLINFO_HTTP_CODE);
        if ($httpStatus > 399) {
            throw new ParseException('Download failed, file may have been deleted.', $httpStatus);
        }
        $this->mimeType = curl_getinfo($rest, CURLINFO_CONTENT_TYPE);
        $this->data = $response;
        curl_close($rest);

        return $response;
    }

    private function getMimeTypeForExtension($extension)
    {
        $knownTypes = [
            'ai'      => 'application/postscript',
            'aif'     => 'audio/x-aiff',
            'aifc'    => 'audio/x-aiff',
            'aiff'    => 'audio/x-aiff',
            'asc'     => 'text/plain',
            'atom'    => 'application/atom+xml',
            'au'      => 'audio/basic',
            'avi'     => 'video/x-msvideo',
            'bcpio'   => 'application/x-bcpio',
            'bin'     => 'application/octet-stream',
            'bmp'     => 'image/bmp',
            'cdf'     => 'application/x-netcdf',
            'cgm'     => 'image/cgm',
            'class'   => 'application/octet-stream',
            'cpio'    => 'application/x-cpio',
            'cpt'     => 'application/mac-compactpro',
            'csh'     => 'application/x-csh',
            'css'     => 'text/css',
            'dcr'     => 'application/x-director',
            'dif'     => 'video/x-dv',
            'dir'     => 'application/x-director',
            'djv'     => 'image/vnd.djvu',
            'djvu'    => 'image/vnd.djvu',
            'dll'     => 'application/octet-stream',
            'dmg'     => 'application/octet-stream',
            'dms'     => 'application/octet-stream',
            'doc'     => 'application/msword',
            'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'docm'    => 'application/vnd.ms-word.document.macroEnabled.12',
            'dotm'    => 'application/vnd.ms-word.template.macroEnabled.12',
            'dtd'     => 'application/xml-dtd',
            'dv'      => 'video/x-dv',
            'dvi'     => 'application/x-dvi',
            'dxr'     => 'application/x-director',
            'eps'     => 'application/postscript',
            'etx'     => 'text/x-setext',
            'exe'     => 'application/octet-stream',
            'ez'      => 'application/andrew-inset',
            'gif'     => 'image/gif',
            'gram'    => 'application/srgs',
            'grxml'   => 'application/srgs+xml',
            'gtar'    => 'application/x-gtar',
            'hdf'     => 'application/x-hdf',
            'hqx'     => 'application/mac-binhex40',
            'htm'     => 'text/html',
            'html'    => 'text/html',
            'ice'     => 'x-conference/x-cooltalk',
            'ico'     => 'image/x-icon',
            'ics'     => 'text/calendar',
            'ief'     => 'image/ief',
            'ifb'     => 'text/calendar',
            'iges'    => 'model/iges',
            'igs'     => 'model/iges',
            'jnlp'    => 'application/x-java-jnlp-file',
            'jp2'     => 'image/jp2',
            'jpe'     => 'image/jpeg',
            'jpeg'    => 'image/jpeg',
            'jpg'     => 'image/jpeg',
            'js'      => 'application/x-javascript',
            'kar'     => 'audio/midi',
            'latex'   => 'application/x-latex',
            'lha'     => 'application/octet-stream',
            'lzh'     => 'application/octet-stream',
            'm3u'     => 'audio/x-mpegurl',
            'm4a'     => 'audio/mp4a-latm',
            'm4b'     => 'audio/mp4a-latm',
            'm4p'     => 'audio/mp4a-latm',
            'm4u'     => 'video/vnd.mpegurl',
            'm4v'     => 'video/x-m4v',
            'mac'     => 'image/x-macpaint',
            'man'     => 'application/x-troff-man',
            'mathml'  => 'application/mathml+xml',
            'me'      => 'application/x-troff-me',
            'mesh'    => 'model/mesh',
            'mid'     => 'audio/midi',
            'midi'    => 'audio/midi',
            'mif'     => 'application/vnd.mif',
            'mov'     => 'video/quicktime',
            'movie'   => 'video/x-sgi-movie',
            'mp2'     => 'audio/mpeg',
            'mp3'     => 'audio/mpeg',
            'mp4'     => 'video/mp4',
            'mpe'     => 'video/mpeg',
            'mpeg'    => 'video/mpeg',
            'mpg'     => 'video/mpeg',
            'mpga'    => 'audio/mpeg',
            'ms'      => 'application/x-troff-ms',
            'msh'     => 'model/mesh',
            'mxu'     => 'video/vnd.mpegurl',
            'nc'      => 'application/x-netcdf',
            'oda'     => 'application/oda',
            'ogg'     => 'application/ogg',
            'pbm'     => 'image/x-portable-bitmap',
            'pct'     => 'image/pict',
            'pdb'     => 'chemical/x-pdb',
            'pdf'     => 'application/pdf',
            'pgm'     => 'image/x-portable-graymap',
            'pgn'     => 'application/x-chess-pgn',
            'pic'     => 'image/pict',
            'pict'    => 'image/pict',
            'png'     => 'image/png',
            'pnm'     => 'image/x-portable-anymap',
            'pnt'     => 'image/x-macpaint',
            'pntg'    => 'image/x-macpaint',
            'ppm'     => 'image/x-portable-pixmap',
            'ppt'     => 'application/vnd.ms-powerpoint',
            'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'potx'    => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx'    => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppam'    => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'pptm'    => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'potm'    => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'ppsm'    => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'ps'      => 'application/postscript',
            'qt'      => 'video/quicktime',
            'qti'     => 'image/x-quicktime',
            'qtif'    => 'image/x-quicktime',
            'ra'      => 'audio/x-pn-realaudio',
            'ram'     => 'audio/x-pn-realaudio',
            'ras'     => 'image/x-cmu-raster',
            'rdf'     => 'application/rdf+xml',
            'rgb'     => 'image/x-rgb',
            'rm'      => 'application/vnd.rn-realmedia',
            'roff'    => 'application/x-troff',
            'rtf'     => 'text/rtf',
            'rtx'     => 'text/richtext',
            'sgm'     => 'text/sgml',
            'sgml'    => 'text/sgml',
            'sh'      => 'application/x-sh',
            'shar'    => 'application/x-shar',
            'silo'    => 'model/mesh',
            'sit'     => 'application/x-stuffit',
            'skd'     => 'application/x-koan',
            'skm'     => 'application/x-koan',
            'skp'     => 'application/x-koan',
            'skt'     => 'application/x-koan',
            'smi'     => 'application/smil',
            'smil'    => 'application/smil',
            'snd'     => 'audio/basic',
            'so'      => 'application/octet-stream',
            'spl'     => 'application/x-futuresplash',
            'src'     => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc'  => 'application/x-sv4crc',
            'svg'     => 'image/svg+xml',
            'swf'     => 'application/x-shockwave-flash',
            't'       => 'application/x-troff',
            'tar'     => 'application/x-tar',
            'tcl'     => 'application/x-tcl',
            'tex'     => 'application/x-tex',
            'texi'    => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tif'     => 'image/tiff',
            'tiff'    => 'image/tiff',
            'tr'      => 'application/x-troff',
            'tsv'     => 'text/tab-separated-values',
            'txt'     => 'text/plain',
            'ustar'   => 'application/x-ustar',
            'vcd'     => 'application/x-cdlink',
            'vrml'    => 'model/vrml',
            'vxml'    => 'application/voicexml+xml',
            'wav'     => 'audio/x-wav',
            'wbmp'    => 'image/vnd.wap.wbmp',
            'wbmxl'   => 'application/vnd.wap.wbxml',
            'wml'     => 'text/vnd.wap.wml',
            'wmlc'    => 'application/vnd.wap.wmlc',
            'wmls'    => 'text/vnd.wap.wmlscript',
            'wmlsc'   => 'application/vnd.wap.wmlscriptc',
            'wrl'     => 'model/vrml',
            'xbm'     => 'image/x-xbitmap',
            'xht'     => 'application/xhtml+xml',
            'xhtml'   => 'application/xhtml+xml',
            'xls'     => 'application/vnd.ms-excel',
            'xml'     => 'application/xml',
            'xpm'     => 'image/x-xpixmap',
            'xsl'     => 'application/xml',
            'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlsm'    => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xltm'    => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xlam'    => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'    => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xslt'    => 'application/xslt+xml',
            'xul'     => 'application/vnd.mozilla.xul+xml',
            'xwd'     => 'image/x-xwindowdump',
            'xyz'     => 'chemical/x-xyz',
            'zip'     => 'application/zip',
        ];

        if (isset($knownTypes[$extension])) {
            return $knownTypes[$extension];
        }

        return 'unknown/unknown';
    }
}
