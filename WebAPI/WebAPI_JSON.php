<?php
/**
 * Project: WebAPI_JSON:: JSON API
 * File:    WebAPI/WebAPI_JSON.php
 *
 * WebAPI_JSON class는 JSON encode시에 UTF-8 관련 conflict를
 * 고민하지 않고 사용할 수 있게 해 준다.
 *
 * @category    HTTP
 * @package     WebAPI
 * @subpackage  WebAPI_JSON
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 2014, JoungKyun.Kim
 * @license     BSD License
 * @version     $Id$
 * @link        http://pear.oops.org/package/WebAPI
 * @filesource
 * @since       1.0.3
 */

/**
 * JSON API for WebAPI Package
 *
 * UTF-8 관련 conflict에 상관 없이 json encoding
 *
 * @package     WebAPI
 */
Class WebAPI_JSON {
	// {{{ +-- static private (mixed) normalize ($v)
	/**
	 * JSON encode시의 UTF-8 conflict 문제를 해결하기 위해
	 * ASCII 외의 문자는 모두 urlencode 처리한다.
	 */
	static private function normalize ($var) {
		if ( ! is_object ($var) && ! is_array ($var) )
			return urlencode ($var);

		foreach ( $var as $key => $val ) {
			if ( is_object ($val) || is_array ($val) )
				$buf[urlencode ($key)] = self::multibyte ($val);
			else
				$buf[urlencode ($key)] = urlencode ($val);
		}

		return $buf;
	}
	// }}}

	// {{{ +-- static public (string) encode ($data, $nopretty = false)
	/**
	 * utf8 conflict 를 해결한 json encode wrapper
	 *
	 * @access public
	 * @return string
	 * @param mixed encode할 변수
	 * @param bool pretty 출력 여부. 기본값 false이며 이는 pretty 출력
	 *             을 의미한다. pretty 인자는 php 5.4 부터 지원한다.
	 */
	static public function encode ($data, $nopretty = false) {
		if ( is_object ($data) || is_array ($data) ) {
			if ( ! count ($data) )
				return json_encode (array ());
		} else {
			if ( ! ($data = trim ($data)) )
				return '';
		}

		// since php 5.4.0
		if ( defined ('JSON_UNESCAPED_UNICODE') ) {
			$opt = JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE;
			if ( ! $nopretty )
				$opt |= JSON_PRETTY_PRINT;
			return json_encode ($data, $opt);
		}

		// under php 5.3.x
		$data = self::nomalize ($data);
		return urldecode (json_encode ($data, JSON_NUMERIC_CHECK));
	}
	// }}}
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
