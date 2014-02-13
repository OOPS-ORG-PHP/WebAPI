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
 * @copyright   (c) 2014 OOPS.org
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
 * import WebAPI_Browser API
 */
require_once 'WebAPI/WebAPI_Browser.php';

/**
 * import WebAPI_Mimetype API
 */
require_once 'WebAPI/WebAPI_Mimetype.php';

/**
 * import WebAPI_JSON API
 */
require_once 'WebAPI/WebAPI_JSON.php';


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
	 * @const WebAPI::UTF8 1
	 */
	const UTF8 = 1;
	/**
	 * @const WebAPI::EUCKR 2
	 */
	const EUCKR = 2;

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

	// {{{ +-- static public (boolean) is_hangul (&$s, $division = false)
	/**
	 * 주어진 값에 한글(UTF8/EUC-KR)이 포함 되어 있는지를 판단
	 *
	 * 이 method의 경우에는 한글이 아닌 다른 연속된 multibyte 문자의
	 * 마지막 바이트와 첫번째 바이트에 의한 오차가 발생할 수 있으므로
	 * 정확도가 필요할 경우에는 KSC5601::is_ksc5601 method를 이용하기
	 * 바란다.
	 *
	 * @access public
	 * @return bool
	 * @param  string
	 * @praam  bool   (optional) true로 설정할 경우, EUC-KR과 UTF-8을
	 *                구분하여 결과값을 반환한다.
	 *     - ASCII returns false
	 *     - UTF-8 returns WebAPI::UTF8
	 *     - EUC-KR returns WebAPI::EUCKR
	 * @sinse 1.0.1 added 2th parameter.
	 */
	static public function is_hangul (&$s, $division = false) {
		// UTF-8 case
		if ( preg_match ('/[\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $s) )
			return $division ? self::UTF8 : true;

		// EUC-KR case
		if ( preg_match ('/[\xA1-\xFE]/', $s) )
			return $division ? self::EUCKR : true;

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

	// {{{ +-- static public (string) substr ($data, $start, $end = false)
	/**
	 * Return part of a string
	 *
	 * 사용법은 php의 native substr과 동일하다.
	 *
	 * native substr과의 차이는, 시작과 끝은 한글이 깨지는 문제를 해결을
	 * 한다. 이 함수는 한글을 2byte로 처리를 하며, UTF-8이라도 한글을 길이는
	 * 2byte로 계산을 하여 반환한다.
	 *
	 * @return string
	 * @param string  The input string. Must be one character or longer.
	 * @param int     start position
	 * @param int     (optional) length of returning part.
	 */
	static public function substr ($data, $start, $end = false) {
		if ( ! ($type = self::is_hangul ($data, true)) )
			return substr ($data, $start, $end);

		if ( $end === 0 )
			return null;

		if ( $type == self::UTF8 )
			$data = iconv ('UTF-8', 'CP949', $data);

		if ( $start < 0 )
			$start = strlen ($data) + $start;

		if ( $end === false )
			$end = strlen ($data) - $start;
		else if ( $end < 0 )
			$end = strlen ($data) + $end - $start;

		if ( $end < 2 )
			return null;

		if ( $start > 0 ) {
			$buf = substr ($data, 0, $start);
			$buf = preg_replace ('/[a-z0-9]|([\x80-\xFE].)/', '', $buf);
			if ( strlen ($buf) != 0 )
				$start--;
		}

		if ( $data[$start - 1] & 0x80 )
			$start--;

		$data = substr ($data, $start, $end);
		$data = preg_replace ('/(([\x80-\xFE].)*)[\x80-\xFE]?$/', '\\1', $data);

		if ( $type === self::UTF8 )
			return iconv ('CP949', 'UTF-8', $data);

		return $data;
	}
	// }}}

	// {{{ +-- static public (boolean) xss (&$str)
	/**
	 * XSS 탐지
	 *
	 *  - Javascript event(onmouseover 등)가 존재할 경우
	 *  - iframe, frame, script, style, link, meta, head tag가 있을 경우
	 *  - src 또는 href attribute에 javascript를 사용할 경우
	 *  - css background* 나 html background* attribute가 존재할 경우
	 *
	 *  - 예외 사항
	 *    . youtube link 예외 처리 (iframe)
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
			'/<[\s]*(iframe)[\s>]+/i',
			'/<[\s]*(script|frame|style|link|meta|head)[\s>]+/i',
			'/background[\s]*(:[\s]*url|=)/i',
			'/(src|href)[\s]*=["\'\s]*(javascript|&#106;|&#74;|%6A|%4A)/i',
			'/on(' . $event . ')[\s]*=/i'
		);

		foreach ( $src as $filter ) {
			if ( preg_match ($filter, $str, $m) ) {
				if ( self::xss_youtube_ext ($m[1], $str) === true )
					continue;

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

			if ( ! preg_match ('!^image/!i', $buf->{'Content-Type'}) ) {
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

	// {{{ +-- private xss_youtube_ext ($filter, &$data)
	/*
	 * self::xss 체크시에 youbute의 iframe은 허가
	 */
	private function xss_youtube_ext ($filter, &$data) {
		if ( strtolower ($filter) != 'iframe' )
			return false;

		if ( ! preg_match_all ('/<iframe[^>]+>/i', $data, $m) )
			return false;

		$dom = new DOMDocument ();
		$dom->encoding = 'utf-8';
		$dom->formatOutput = true;

		foreach ( $m as $v ) {
			@$dom->loadHTML ($v[0] . '</iframe>');
			$iframe = $dom->getElementsByTagName ('iframe');
			if ( $iframe->length < 1 )
				continue;

			$src = $iframe->item(0)->getAttribute ('src');

			if ( ! preg_match ('!^(http[s]?:)?//(www\.)?youtube(-nocookie)?\.(be|com)/!i', trim ($src)) )
				return false;
		}

		return true;
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
	 * File fucntions
	 */
	// {{{ +-- static public (string) get_file_extension ($f, $post = false)
	/**
	 * 파일의 확장자를 반환
	 *
	 * @access public
	 * @return string  파일 확장자. 확장자가 없을 경우 null 반환.
	 * @param  string  파일 이름 또는 경로
	 * @param  boolean (optional) true로 설정시에 $_FILES[첫번째인자값]['name']의
	 *         값에서 확장자를 반환.
	 */
	static public function get_file_extension ($f, $post = false) {
		if ( $post === true ) {
			if ( defined ($_FILES[$f]) )
				$f = $_FILES[$f]['name'];
		}

		if ( ! preg_match ('/\./', $f) )
			return null;

		$ext = preg_split ('/\./', $f);
		$tail = $ext[count ($ext) - 1];

		return $tail ? strtolower ($tail) : null;
	}
	// }}}

	// {{{ +-- static public (string) mimetype ($name)
	/**
	 * 주어진 파일의 mimetype을 결정한다.
	 *
	 * @access public
	 * @param string mimetype 정보를 확인할 파일경로
	 * @return string
	 */
	static public function mimetype ($name) {
		return WebAPI_Mimetype::mime ($name);
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
		return WebAPI_Filter::context ($text, $pattern);
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
		return WebAPI_Filter::context_file ($text, $patfile);
	}
	// }}}

	// {{{ +-- static public (boolean) filter_ip (&$ip)
	/**
	 * 접속 IP($_SERVER['REMOTE_ADDR'])가 주어진 CIDR/MASK에 포함이 되는지 확인
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

	// {{{ +-- static public (stdClass) browser (void)
	/**
	 * Broswer API
	 *
	 * Browser 정보를 구함
	 *
	 * @access public
	 * @return stdClass
	 * @sinse 1.0.2
	 */
	static public function browser ($u = null) {
		return WebAPI_Browser::exec ($u);
	}
	// }}}

	/**
	 * JSON function
	 */

	// {{{ +-- static public (string) json_encode ($text, $nopretty = false)
	/**
	 * utf8 conflict 를 해결한 json encode wrapper
	 *
	 * @access public
	 * @return string
	 * @param mixed encode할 변수
	 * @param bool pretty 출력 여부. 기본값 false이며 이는 pretty 출력
	 *             을 의미한다. pretty 인자는 php 5.4 부터 지원한다.
	 * @since 1.0.3
	 */
	static public function json_encode ($text, $nopretty = false) {
		return WebAPI_JSON::encode ($text, $nopretty);
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
