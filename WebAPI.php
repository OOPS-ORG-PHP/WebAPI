<?php
/**
 * Project: WebAPI:: String API class for WEB<br>
 * File:    WebAPI.php
 *
 * WebAPI 패키지는 WEB에서 사용되는 문자열 관련 API를 제공한다.
 *
 * 예제:
 * {@example pear_HTTPRelay/tests/test.php}
 *
 * @category    HTTP
 * @package     WebAPI
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 1997-2013 OOPS.org
 * @license     BSD License
 * @version     SVN: $Id$
 * @link        http://pear.oops.org/package/WebAPI
 * @since       File available since release 0.0.1
 * @filesource
 */

/**
 * import oops/HTTPRelay pear package
 */
require_once 'HTTPRelay.php';
/**
 * import WebAPI_Autolink API
 */
require_once 'WebAPI/WebAPI_Autolink.php';
/**
 * import WebAPI_Filtering API
 */
require_once 'WebAPI/WebAPI_Filter.php';


/**
 * WebAPI 패키지는 WEB에서 사용되는 문자열 관련 API를 제공한다.
 *
 * @package WebAPI
 */
Class WebAPI {
	// {{{ properties
	/**#@+
	 * @access public
	 */
	/**
	 * UTF8 처리 여부. 기본값 true
	 * @var bool
	 */
	static public $utf8 = true;

	/**
	 * XSS check후 status를 저장
	 * @var object
	 */
	static public $xssinfo = null;
	/**#@-*/
	// }}}

	// {{{ +-- static public (boolean) is_injection (&$s)
	/**
	 * URL parameter 값에 inject 공격이 가능한 코드 감지
	 * 막을 대상:
	 *      - ..   이전 경로
	 *      - semi colon
	 *      - single or double quote
	 *      - %00 (null byte)
	 *      - %25 (%)
	 *      - %27 (')
	 *      - %2e (.)
	 *
	 * @access public
	 * @return boolean
	 * @param  string
	 */
	static public function is_injection (&$s) {
		if ( preg_match ('/\.\.\/|[;\'"]|%(00|25|27|2e/i', $s) ) {
			return true;
		}

		return false;
	}
	// }}}

	// {{{ +-- static public (bool) is_alpha (&$c)
	/**
	 * 주어진 문자열에 숫자, 알파벳, whtie space만으로 구성이
	 * 되어 있는지 확인
	 *
	 * @access public
	 * @return bool
	 * @param string 입력값의 처음 1byte만 판단함
	 */
	static public function is_alpha (&$c) {
		return preg_match ('/^[0-9a-z]+$/i', $c);
	}
	// }}}

	// {{{ +-- static public (boolean) is_hangul (&$s)
	/**
	 * 주어진 값에 한글(UTF8/EUC-KR)이 포함 되어 있는지를 판단
	 *
	 * 이 method의 경우에는 한글이 아닌 다른 연속된 multibyte 문자의
	 * 마지막 바이트와 첫번째 바이트에 의한 오차가 발생할 수 있으므로
	 * 정확도가 필요할 경우에는 KSC5601::is_ksc5601 method를 이용하기
	 * 바란다.
	 *
	 * @access public
	 * @return boolean
	 * @param  string
	 */
	static public function is_hangul (&$s) {
		// UTF-8 case
		if ( preg_match ('/[\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $s) )
			return true;

		// EUC-KR case
		if ( preg_match ('/[\xA1-\xFE]/', $s) )
			return true;

		return false;
	}
	// }}}

	// {{{ +-- static public (boolean) is_proxy (void)
	/**
	 * Check this connection whethad access through Proxy server.
	 *
	 * @access public
	 * @return boolean
	 * @param  array (options) User define 헤더. '-'는 '_'로 표시 해야 함.
	 */
	static public function is_proxy ($udef = null) {
		foreach ( $_SERVER as $k => $v ) {
			switch ($k) {
				case 'HTTP_VIA':
				case 'HTTP_CLIENT_IP':
				case 'HTTP_PROXY':
				case 'HTTP_SP_HOST':
				case 'HTTP_COMING_FROM':
				case 'HTTP_X_COMING_FROM':
				case 'HTTP_FORWARDED':
				case 'HTTP_X_FORWARDED':
				case 'HTTP_FORWARDED_FOR':
				case 'HTTP_X_FORWARDED_FOR':
					return true;
			}
		}

		if ( $udef === null )
			return false;

		foreach ( $udef as $v ) {
			if ( $_SERVER[$v] )
				return true;
		}

		return false;
	}
	// }}}

	// {{{ +-- static public (boolean) is_email (&$v)
	/**
	 * 주어진 이메일이 valid한지 확인
	 *
	 * @access public
	 * @return boolean
	 * @param string check email address
	 */
	static public function is_email (&$v) {
		WebAPI_Autolink::$utf8 = self::$utf8;
		return WebAPI_Autolink::is_email ($v);
	}
	// }}}

	// {{{ +-- static public (boolean) is_url (&$v)
	/**
	 * 주어진 url이 valid한지 확인
	 *
	 * @access public
	 * @return boolean
	 * @param string check url address
	 */
	static public function is_url (&$v) {
		WebAPI_Autolink::$utf8 = self::$utf8;
		return WebAPI_Autolink::is_url ($v);
	}
	// }}}

	// {{{ +-- static public (boolean) is_protocol (&$v)
	/**
	 * 주어진 protocol이 valid한지 확인
	 *
	 * @access public
	 * @return boolean
	 * @param string check url address
	 */
	static public function is_protocol (&$v) {
		WebAPI_Autolink::$utf8 = self::$utf8;
		return WebAPI_Autolink::is_protocol ($v);
	}
	// }}}

	// {{{ +-- static public (string) get_file_extension ($f, $post = false)
	/**
	 * 파일의 확장자를 반환
	 *
	 * @access public
	 * @return string  파일 확장자. 확장자가 없을 경우 null 반환.
	 * @param  string  파일 이름 또는 경로
	 * @param  boolean (optional) true로 설정시에 $_POST[첫번째인자값]['name']의
	 *         값에서 확장자를 반환.
	 */
	static public function get_file_extension ($f, $post = false) {
		if ( $post === true ) {
			if ( defined ($_POST[$f]) )
				$f = $_POST[$f]['name'];
		}

		if ( ! preg_match ('/\./', $f) )
			return null;

		$ext = preg_split ('/\./', $f);
		$tail = $ext[count ($ext) - 1];

		return $tail ? $tail : null;
	}
	// }}}

	// {{{ +-- static public (string) client_ip ($udef_haeder = false)
	/**
	 * Client의 IP를 구한다. Proxy header가 존재할 경우, 첫번째
	 * Proxy의 forward IP를 반환한다.
	 *
	 * @access public
	 * @return string CIDR
	 * @param  array  (optional) User Define additional Proxy Header list
	 */
	static public function client_ip ($udef_haeder = false) {
		$myip = $_SERVER['REMOTE_ADDR'];

		$headers = array (
			'HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_COMING_FROM',
			'HTTP_X_FORWARDED','HTTP_FORWARDED_FOR','HTTP_FORWARDED',
			'HTTP_VIA', 'HTTP_COMING_FROM','HTTP_PROXY','HTTP_SP_HOST',
		);

		if ( is_array ($udef_header) )
			$headers = array_merge ($headers, $udef_header);

		foreach ( $headers as $v ) {
			if ( $_SERVER[$v] )
				return preg_replace ('/[\s,]+/', '', $myip);
		}
		
		return $myip;
	}
	// }}}

	// {{{ +-- static public (void) autolink (&$str)
	/**
	 * 주어진 문장속의 URL을 hyper link로 변경
	 *
	 * @access public
	 * @return void
	 * @param string
	 */
	static public function autolink (&$str) {
		WebAPI_Autolink::$utf8 = self::$utf8;
		WebAPI_Autolink::execute ($str);
	}
	// }}}

	// {{{ +-- static public (boolean) xss (&$str)
	/**
	 * XSS 탐지
	 *
	 * @access public
	 * @return boolean XSS 탐지시 true
	 * @param string
	 */
	static public function xss (&$str) {
		if ( self::$xssinfo === null )
			self::$xssinfo = new stdClass;

		// javascritp event
		$event =
			'abort|activate|afterprint|afterupdate|beforeactivate|beforecopy|' .
			'beforecut|beforedeactivate|beforeeditfocus|beforepaste|beforeprint|' .
			'beforeunload|beforeupdate|blur|bounce|cellchange|change|click|' .
			'contextmenu|controlselect|copy|cut|dataavailable|datasetchanged|' .
			'datasetcomplete|dblclick|deactivate|drag|dragend|dragenter|' .
			'dragleave|dragover|dragstart|drop|error|errorupdate|filterchange|' .
			'finish|focus|focusin|focusout|help|keydown|keypress|keyup|' .
			'layoutcomplete|load|losecapture|mousedown|mouseenter|mouseleave|' .
			'mousemove|mouseout|mouseover|mouseup|mousewheel|move|moveend|' .
			'movestart|paste|propertychange|readystatechange|reset|resize|' .
			'resizeend|resizestart|rowenter|rowexit|rowsdelete|rowsinserted|' .
			'scroll|select|selectionchange|selectstart|start|stop|submit|unload';

		$src = array (
			'/<[\s]*(script|iframe|frame|style|link|meta|head)[\s>]+/i',
			'/background[\s]*(:[\s]*url|=)/i',
			'/(src|href)[\s]*=["\'\s]*(javascript|&#106;|&#74;|%6A|%4A)/i',
			'/on(' . $event . ')[\s]*=/i'
		);

		foreach ( $src as $filter ) {
			if ( preg_match ($filter, $str) ) {
				self::$xssinfo->status = true;
				self::$xssinfo->msg = sprintf (
					'\'%s\' pattern is matched. This is strongly doubt XSS attack.',
					$filter
				);
				return true;
			}
		}

		# img src에 image가 아닌 소스 확인
		$imgs = self::img_tags ($str, 'img');
		$http = new HTTPRelay;
		foreach ( $imgs as $path ) {
			// 외부 URL이 아닐 경우에는 skip
			if ( ! preg_match ('!^(http[s]?)*//!i', $path) )
				continue;

			if ( preg_match ('!^//!', $path) )
				$path = sprintf ('%s:%s', $_SERVER['HTTPS'] ? 'https' : 'http', $path);

			if ( ($buf = $http->head ($path, 1)) === false )
				continue;

			if ( ! preg_match ('!^image\/!i', $buf->{'Content-Type'}) ) {
				self::$xssinfo->status = true;
				self::$xssinfo->msg = sprintf (
					'The value of image src property (%s) is assumed that is not image.' .
					'This is storngly doubt XSS attack.',
					$path
				);
				return true;
			}
		}

		return false;
	}
	// }}}

	// {{{ +-- private (array) img_tags ($buf)
	/**
	 * 주어진 문장에서 img의 src property 값을 배열로 반환한다.
	 *
	 * @access private
	 * @return array
	 * @param string
	 * @param string
	 */
	private function img_tags ($buf) {
		$buf = preg_replace ('/\r?\n/', ' ', $buf);
		$p = preg_match_all ('/<[\s]*img[^>]*src=[\'"\s]*([^\'"\s>]+)/i', $buf, $matches);

		if ( ! $p )
			return array ();

		return $matches[1];
	}
	// }}}

	/**
	 * Filtering context
	 */

	// {{{ +-- static public (boolean) filter_context (&$text, &$pattern)
	/**
	 * 정규식을 이용하여 필터링
	 *
	 * @access public
	 * @return boolean
	 * @param string 필터링할 문자열
	 * @param string 필터링 패턴. 패턴은 PCRE 정규 표현식을 이용한다.
	 */
	static public function filter_context (&$text, &$pattern) {
		return WebAPI::context ($text, $pattern);
	}
	// }}}

	// {{{ +-- static public (boolean) filter_context_file (&$text, &$patfile)
	/**
	 * 필터링 파일을 이용하여 필터링
	 *
	 * @access public
	 * @return boolean
	 * @param string 필터링할 문자열
	 * @param string 필터링 패턴 파일. 패턴은 PCRE 정규 표현식을 이용한다.
	 */
	static public function filter_context_file (&$text, &$patfile) {
		return WebAPI_Finter::context_file ($text, $patfile);
	}
	// }}}

	// {{{ +-- static public (boolean) filter_ip (&$ip)
	/**
	 * 접속 IP가 주어진 CIDR/MASK에 포함이 되는지 확인
	 *
	 * @access public
	 * @return boolean
	 * @param  string  매칭시킬 IP 또는 IP 블럭
	 *     - 허가된 표현식
	 *       - 1.1.1.1
	 *       - 1.1.1.1/24
	 *       - 1.1.1.1/255.255.255.0
	 *       - 1.1.1. (1.1.1.0/24)
	 *       - 1.1. (1.1.0.0/16)
	 *       - 1.1.1.1 - 2.2.2.2 (range)
	 */
	static public function filter_ip (&$ip) {
		return WebAPI_Filter::ip ($ip);
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
