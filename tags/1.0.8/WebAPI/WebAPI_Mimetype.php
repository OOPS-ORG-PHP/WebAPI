<?php
/**
 * Project: WebAPI_Mimetype :: Get mimetype for file
 * File:    WebAPI/WebAPI_Mimetype.php
 *
 * WebAPI_Mimetype class는 file의 mimetype 정보를 처리한다.
 *
 * @category    HTTP
 * @package     WebAPI
 * @subpackage  WebAPI_Mimetype
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 2015, JoungKyun.Kim
 * @license     BSD License
 * @version     $Id$
 * @link        http://pear.oops.org/package/WebAPI
 * @filesource
 * @since       1.0.2
 */

/**
 * Get browser information
 *
 * WebAPI_Mimetype class는 file의 mimetype 정보를 처리한다.
 *
 * @package     WebAPI
 */
Class WebAPI_Mimetype {
	/**
	 * 주어진 파일의 mimetype을 결정한다.
	 *
	 * @access public
	 * @param string mimetype 정보를 확인할 파일경로
	 * @return string
	 */
	static public function mime ($file) {
		if ( ! file_exists ($file) )
			return self::pure_mime ($file);

		if ( ! extension_loaded ('fileinfo') ) {
			if ( function_exists ('mime_content_type') )
				return mime_content_type ($file);

			return self::pure_mime ($file);
		}

		$desc = finfo_open (FILEINFO_MIME_TYPE);
		$buf = finfo_file ($desc, $file);
		finfo_close ($desc);

		return $buf;
	}

	private function pure_mime (&$file) {
		// get file extension
		if ( ($file = WebAPI::get_file_extension ($file)) == null )
			return 'application/octet-stream';

		require_once 'WebAPI/pure_mimetype.php';

		return $mime;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim: set filetype=php noet sw=4 ts=4 fdm=marker:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
?>
