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
 * @copyright   (c) 2015, JoungKyun.Kim
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
				$buf[urlencode ($key)] = self::normalize ($val);
			else
				$buf[urlencode ($key)] = urlencode ($val);
		}

		return $buf;
	}
	// }}}

	// {{{ +-- static private (mixed) unnormalize ($v)
	/**
	 * url encode 되어 있는 데이터를 url decode 처리
	 */
	static private function unnormalize ($var, $assoc = true) {
		if ( ! is_object ($var) && ! is_array ($var) )
			return urldecode ($var);

		foreach ( $var as $key => $val ) {
			if ( is_object ($val) || is_array ($val) )
				$buf[urldecode ($key)] = self::unnormalize ($val, $assoc);
			else
				$buf[urldecode ($key)] = urldecode ($val);
		}

		if ( $assoc )
			return $buf;

		return (object) $buf;
	}
	// }}}

	// {{{ +-- static public (string) encode ($data, $nopretty = false)
	/**
	 * utf8 conflict 및 binary data 를 해결한 json encode wrapper
	 *
	 * @access public
	 * @return string
	 * @param mixed encode할 변수
	 * @param bool (optional) pretty 출력 여부. 기본값 false이며 이는 pretty 출력
	 *             을 의미한다. pretty 인자는 php 5.4 부터 지원한다. [기본값 false]
	 * @param bool (optional) binary safe를 위한 normalize를 할지 여부 [기본값 true]
	 */
	static public function encode ($data, $nopretty = false, $normal = true) {
		if ( is_object ($data) || is_array ($data) ) {
			if ( ! count ($data) )
				return json_encode (array ());
		} else {
			if ( ! ($data = trim ($data)) )
				return '';
		}

		if ( $normal ) {
			// for binary data
			$data = self::normalize ($data);
		}

		// since php 5.4.0
		if ( defined ('JSON_UNESCAPED_UNICODE') ) {
			$opt = JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE;
			if ( ! $nopretty )
				$opt |= JSON_PRETTY_PRINT;
			return json_encode ($data, $opt);
		}

		// under php 5.3.x
		if ( $normal )
			return json_encode ($data, JSON_NUMERIC_CHECK);

		$data = self::normalize ($data);
		return urldecode (json_encode ($data, JSON_NUMERIC_CHECK));
	}
	// }}}

	// {{{ +-- static public (string) decode ($data)
	/**
	 * utf8 conflict 및 binary data 를 해결한 json encode wrapper
	 *
	 * @access public
	 * @return stdClass
	 * @param string $data json data
	 * @param int    (optional) User specified recursion depth (default: 512)
	 * @param int    (optional) Bitmask of JSON decode options. Currently only
	 *               JSON_BIGINT_AS_STRING is supported (default is
	 *               to cast large integers as floats)
	 * @since 1.0.5
	 */
	static public function decode ($data, $assoc = false, $depth = 512, $options = 0) {
		$data = json_decode ($data, $assoc, $depth, $options);
		return self::unnormalize ($data, $assoc);
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
