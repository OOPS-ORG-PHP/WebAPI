<?php
/**
 * Project: WebAPI_Autolink :: Auto link API
 * File:    WebAPI/WebAPI_Autolink.php
 *
 * WebAPI_Autolink class는 문맥속의 URL을 hyper link로 만들어
 * 준다.
 *
 * @category    HTTP
 * @package     WebAPI
 * @subpackage  WebAPI_Autolink
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 2018, OOPS.org
 * @license     BSD License
 * @link        http://pear.oops.org/package/WebAPI
 * @filesource
 * @since       0.0.1
 */

/**
 * Autolink API for WebAPI Package
 *
 * WebAPI_Autolink class는 문맥속의 URL을 hyper link로 변경
 *
 * @package     WebAPI
 */
Class WebAPI_Autolink {
	// {{{ properties
	/**#@+
	 * @access private
	 */
	static private $reg_charset = array (
		//'uhan' => '\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}',
		'uhan' => '\p{Hangul}\p{Han}\p{Hiragana}\p{Katakana}',
		'ehan' => '\xA1-\xFE',
	);
	static private $reg = null;
	/**#@-*/

	/**
	 * UTF-8 모드 사용 여부. 기본값 true
	 *
	 * @access public
	 * @var bool
	 */
	static public $utf8 = true;
	// }}}

	// {{{ +-- private (void) set_regex_template (void)
	/**
	 * WebStirng_Autolink class에서 사용할 template 초기화
	 *
	 * @access public
	 * @return void
	 */
	private function set_regex_template () {
		if ( self::$reg !== null )
			return;

		$ext = 'gz|tgz|tar|gzip|zip|rar|mpeg|mpg|exe|rpm|dep|rm|ram|asf' .
				'|ace|viv|avi|mid|gif|jpg|png|bmp|eps|mov';

		$han = self::$utf8 ? self::$reg_charset['uhan'] : self::$reg_charset['han'];

		self::$reg = (object) array (
			'file' => '(\.(' . $ext . ')") target="_blank"',
			'link' => "(https?|s?ftp|telnet|news|mms):\/\/(([{$han}a-z0-9:_\-]+\.[{$han}a-z0-9,:;&#=_~%\[\]?\/.,+\-]+)([.]*[\/a-z0-9\[\]]|=[{$han}]+))",
			'mail' => "([{$han}a-z0-9+_.-]+)@([{$han}a-z0-9_-]+\.[{$han}a-z0-9._-]*[a-z]{2,3}(\?[\s{$han}a-z0-9=&\?;%]+|%[0-9]{2})*)"
		);
	}
	// }}}

	// {{{ +-- static private (void) nomalize (&$v)
	/**
	 * autolink를 하기 전에 autolink와 관련된 사항을 nomalize 한다.
	 *
	 *   - 개행 문자를 \n으로 통일
	 *   - IMG, A tag를 한줄로 만듬
	 *   - style, src, codebase, pluginspace, background tag property을
	 *     한줄로 만듬
	 *   - link의 target과 javascript event를 제거
	 */
	private function nomalize (&$v) {
		$v = preg_replace ("/\r?\n/", "\n", $v);
		$um = self::$utf8 ? 'u' : '';

		$src = array (
			'/<[\s]*(a|img)[\s]*[^>]+>/i',
			'/(style|action|src|codebase|pluginspace|background)[\s]*=[\s]*[\'\"][^\'"]+[\'"]/i'
		);

		$v = preg_replace_callback (
			$src,
			function ($matches) {
				$src[] = '/[\s]+/';
				$des[] = ' ';
				$src[] = '/[\s]*=[\s]*/';
				$des[] = '=';
				$src[] = '/=[\'"][\s]*([^\'"]+)[\s]+[\'"]/';
				$des[] = '="\\1"';
				$src[] = '/[\s]*(target|on[a-z]+)=[^\s>]+/i';
				$des[] = '';
				$src[] = '/<[\s]+/';
				$des[] = '<';
				$src[] = '/[\s]+>/';
				$des[] = '>';
				return preg_replace ($src, $des, $matches[0]);
			},
			$v
		);

		$han = self::$utf8 ? self::$reg_charset['uhan'] : self::$reg_charset['han'];

		// URL 내의 공백문자 처리
		$src = array (
			"!((https?|s?ftp|telnet|news|mms)://[^</]+)(/[^</]+/)!im",
			"!((https?|s?ftp|telnet|news|mms)://.*/)([{$han}a-z0-9,_-]+[\s]+[[{$han}a-z0-9,_-]*\.(jpg|png|gif|psd|txt|html?|(doc|xls|ppt)x?))!{$um}im",
		);
		$v = preg_replace_callback (
			$src,
			function ($matches) {
				$org = trim ($matches[3]);
				if ( ! ($len = strlen ($org)) )
					return '';

				$new = '';
				for ( $i=0; $i<$len; $i++ ) {
					if ( $org[$i] == ' ' || $org[$i] == "\t" )
						$new .= '%20';
					else
						$new .= $org[$i];
				}
				return $matches[1] . $new;
			},
			$v
		);
	}
	// }}}

	// {{{ +-- static public (void) execute (&$v)
	/**
	 * 주어진 문장속의 URL을 hyper link로 변경
	 *
	 * @access public
	 * @return string
	 * @param string 문자열
	 */
	static public function execute (&$v) {
		self::set_regex_template ();
		$reg = &self::$reg;

		$um = self::$utf8 ? 'u' : '';

		self::nomalize ($v);

		# Tag nomalizing -> self::nomalize 로 이동..
		# 아래는 기존의 nomalize 임!
		#
		# &lt; 로 시작해서 3줄뒤에 &gt; 가 나올 경우와
		# IMG tag 와 A tag 의 경우 링크가 여러줄에 걸쳐 이루어져 있을 경우
		# 이를 한줄로 합침 (합치면서 부가 옵션들은 모두 삭제함)
		#$src[] = "/<([^<>\n]*)\n([^<>\n]+)\n([^<>\n]*)>/i";
		#$des[] = '<\\1\\2\\3>';
		#$src[] = "/<([^<>\n]*)\n([^\n<>]*)>/i";
		#$des[] = '<\\1\\2>';
		#$src[] = "/<(A|IMG)[^>]*(href|src)[^=]*=['\"\s]*({$reg->link}|mailto:{$reg->mail})[^>]*>/{$um}i";
		#$des[] = '<\\1 \\2="\\3">';

		# email 형식이나 URL 에 포함될 경우 URL 보호를 위해 @ 을 치환
		$src[] = "/(http|https|ftp|telnet|news|mms):\/\/([^\s@]+)@/i";
		$des[] = '\\1://\\2_HTTPAT_\\3';

		# 특수 문자를 치환 및 html사용시 link 보호
		$src[] = '/&(quot|gt|lt)/i';
		$des[] = '!\\1';
		$src[] = "/<a([^>]*)href=[\"'\s]*({$reg->link})[\"']*[^>]*>/{$um}i";
		$des[] = '<a\\1href="\\3_orig://\\4" target="_blank">';
		$src[] = "/href=[\"'\s]*mailto:({$reg->mail})[\"']*>/{$um}i";
		$des[] = 'href="mailto:\\2#-#\\3">';
		# 1 라인에 중복 되어 있을 수 있으므로 중복되어 사용되지 않는
		# 태크들로 나누어 준다.
		$src[] = "/<([^>]*)(background|src|action)[\s]*=[\"'\s]*({$reg->link})[\"']*/${um}i";
		$des[] = '<\\1\\2="\\4_orig://\\5"';
		$src[] = "/<([^>]*)(codebase|pluginspage|value)[\s]*=[\"'\s]*({$reg->link})[\"']*/${um}i";
		$des[] = '<\\1\\2="\\4_orig://\\5"';
		$src[] = "/<([^>]*)(rsrc)[\s]*=[\"'\s]*({$reg->link})[\"']*/${um}i";
		$des[] = '<\\1\\2="\\4_orig://\\5"';

		# 링크가 안된 url및 email address 자동링크
		$src[] = "/((src|href|base|ground)[\s]*=[\s]*|[^=]|^)({$reg->link})/{$um}i";
		$des[] = '\\1<a href="\\3" target="_blank">\\3</a>';
		$src[] = "/({$reg->mail})/{$um}i";
		$des[] = '<a href="mailto:\\1">\\1</a>';
		$src[] = '/<a href=[^>]+>(<a href=[^>]+>)/i';
		$des[] = '\\1';
		$src[] = '/<\/a><\/a>/i';
		$des[] = '</a>';

		# 보호를 위해 치환한 것들을 복구
		$src[] = '/!(quot|gt|lt)/i';
		$des[] = '&\\1';
		$src[] = '/(http|https|ftp|telnet|news|mms)_orig/i';
		$des[] = '\\1';
		$src[] = '/#-#/';
		$des[] = '@';
		$src[] = "/{$reg->file}/${um}i";
		$des[] = '\\1';

		# email 주소를 변형시킴
		$src[] = "/mailto:[\s]*{$reg->mail}/${um}i";
		$des[] = 'javascript:location.href=\'mailto:\\1\' + \'@\' + \'\\2\';';
		$src[] = "/{$reg->mail}/${um}i";
		$des[] = '\\1&#0064;\\2'; // @ html entity
		$src[] = '/<</';
		$des[] = '&lt;<';
		$src[] = '/>>/';
		$des[] = '>&gt;';

		# email 주소를 변형한 뒤 URL 속의 @ 을 복구
		$src[] = '/_HTTPAT_/';
		$des[] = '@';

		$v = preg_replace ($src, $des, $v);
	}
	// }}}

	// {{{ +-- static public (boolean) is_email ($v)
	/**
	 * 주어진 이메일이 valid한지 확인.
	 *
	 * 도메인 파트의 경우 MX record가 있거나 또는 inverse domain이 설정되어 있어야
	 * true로 판별한다. 도메인 파트가 IP주소일 경우에는 MX record나 inverse domain
	 * 을 체크하지 않는다.
	 *
	 * 다음의 형식을 체크한다.
	 *
	 *     - id@domain.com
	 *     - id@[IPv4]
	 *     - name <id@domain.com>
	 *     - id@domain.com?subject=title&cc=...
	 *     - name <id@domain.com?subject=title&cc=...>
	 *
	 * 이메일 parameter가 존재할 경우에는, CC, BCC, SUBJECT, BODY 만 허가가 된다. 만약,
	 * 이메일 parameter를 체크하고 싶지 않다면, 이 method에 넘기는 값에서 parameter를
	 * 제거하고 넘겨야 한다.
	 *
	 * @access public
	 * @return boolean
	 * @param string check email address
	 */
	static public function is_email ($v) {
		self::set_regex_template ();

		$v = trim ($v);

		// check format "name" <id@domain.com>
		if ( preg_match ('/^[^<]+[\s]*<([^@]+@[^>]+)>$/', $v, $m) )
			$v = $m[1];

		// check sub strings
		$m = preg_split ('/\?/', $v);
		if ( count ($m) > 1 ) {
			if ( self::is_email ($m[0]) === false )
				return false;

			parse_str (substr (strstr ($v, '?'), 1), $sub);
			$v = $m[0];

			// allow CC, BCC, BODY, SUBJECT
			foreach ( $sub as $key => $val ) {
				$key = strtolower ($key);
				switch ($key) {
					case 'cc' :
					case 'bcc' :
						$emails = preg_split ('/,/', $val);
						foreach ( $emails as $email ) {
							if ( self::is_email (trim ($email)) === false )
								return false;
						}
						break;
					case 'body' :
					case 'subject' :
						break;
					default :
						return false;
				}
			}
		}

		// check id@[1.1.1.1]
		$ip = false;
		if ( preg_match ('/^([^@]+)@\[([0-9.]+)\]$/', $v, $m) ) {
			$lv = sprintf ('%lu', ip2long ($m[2]));
			if ( ! $lv || $lv < 16777216 )
				return false;
			$ip = true;
			$v = $m[1] . '@google.com';
		}

		if ( ! preg_match ('/^' . self::$reg->mail . '$/i', $v) )
			return false;

		if ( $ip === true )
			return true;

		// email의 도메인이 MX record가 있거나 inverse domain
		// 셋팅이 되어 있어야 valid 처리한다.
		list ($user, $host) = preg_split ('/@/', $v);
		if ( checkdnsrr ($host, 'MX') || gethostbynamel ($host) )
			return true;

		return false;
	}
	// }}}

	// {{{ +-- static public (boolean) is_url (&$v)
	/**
	 * 주어진 url이 valid한지 확인
	 *
	 * @access public
	 * @return boolean
	 * @param string check email address
	 */
	static public function is_url (&$v) {
		if ( ! self::is_protocol ($v) )
			return false;

		if ( preg_match ('/^' . self::$reg->link . '$/i', $v) )
			return true;

		return false;
	}
	// }}}

	// {{{ +-- static public (boolean) is_protocol (&$v)
	/**
	 * 주어진 protocol이 valid한지 확인
	 *
	 * @access public
	 * @return boolean
	 * @param string check email address
	 */
	static public function is_protocol (&$v) {
		if ( preg_match ('!^(http[s]?|ftp[s]?|telnet|news|mms)://!i', $v) )
			return false;
		return true;
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
