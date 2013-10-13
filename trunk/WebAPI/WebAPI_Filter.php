<?php
/**
 * Project: WebAPI_Filter :: Auto link API
 * File:    WebAPI/WebAPI_Filter.php
 *
 * WebAPI_Filter class는 단어 및 IP 필터링을 제공한다.
 *
 * @category    HTTP
 * @package     WebAPI
 * @subpackage  WebAPI_Filter
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 2013, JoungKyun.Kim
 * @license     BSD License
 * @version     $Id$
 * @link        http://pear.oops.org/package/WebAPI
 * @filesource
 * @since       0.0.1
 */

/**
 * import oops/IPCALC pear package
 */
require_once 'ipcalc.php';

/**
 * Autolink API for WebAPI Package
 *
 * WebAPI_Filter class는 단어 및 IP 필터링을 제공한다.
 *
 * @package     WebAPI
 */
Class WebAPI_Filter {
	// {{{ properties
	/**#@+
	 * @access private
	 */
	/**
	 * IPCALC constructor
	 * @var IPCALC
	 */
	static private $ip = null;
	/**#@-*/
	// }}}

	// {{{ +-- private (boolean) context_normalize (&$v)
	/**
	 * 필터링 패턴을 정규화 함
	 *
	 * @access private
	 * @return boolean
	 * @param string 필터링 패턴
	 */
	private function context_normalize (&$v) {
		// 첫라인이 # 으로 시작하거나 공백라인일 경우 SKIP
		if ( preg_match ('/^#|^[\s]*$/', $v) ) {
			$v = null;
			return false;
		}

		$src = array ('/!/', '/\x5c#/', '/#.*/', '\x5c__sharp__');
		$des = array ('\!', '\__sharp__', '', '\#');
		$v = preg_replace ($src, $des, $v);

		return true;
	}
	// }}}

	// {{{ +-- static public (boolean) context (&$text, &$pattern)
	/**
	 * 정규식을 이용하여 필터링
	 *
	 * @access public
	 * @return boolean
	 * @param string 필터링할 문자열
	 * @param string 필터링 패턴. 정규식은 PCRE를 사용한다.
	 */
	static public function context (&$text, &$pattern) {
		$lines = preg_split ("/\r?\n/", $pattern);

		foreach ( $lines as $line ) {
			if ( self::context_nomalize ($line) === false )
				continue;

			if ( preg_match ('/' . $line . '/ui', $text, $matches) )
				return true;
		}

		return false;
	}
	// }}}

	// {{{ +-- static public (boolean) context_file (&$text, &$patfile)
	/**
	 * 필터링 파일을 이용하여 필터링
	 *
	 * @access public
	 * @return boolean
	 * @param string 필터링할 문자열
	 * @param string 필터링 DB 파일
	 */
	static public function context_file (&$text, &$patfile) {
		if ( ! file_exists ($patfile) )
			return false;

		$pattern = file (
			$patfile,
			FILE_USE_INCLUDE_PATH|FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES
		);

		return self::context ($text, $pattern);
	}
	// }}}

	// {{{ +-- private (string) ip_nomalize (&$v)
	/**
	 * @access public
	 * @return string
	 * @param string 매칭할 IP
	 */
	private function ip_nomalize (&$v) {
		// Devided Network And Subnet
		list ($network, $subnet) = preg_split ('!/!', $v);

		if ( self::$ip === null )
			self::$ip = new IPCALC;
		$ip = &self::$ip;

		// Nomalizing Subnet
		if ( $subnet ) {
			if ( is_numeric ($subnet) )
				$subnet = $ip->prefix2mask ($subnet);
			else {
				if ( ! $ip->valid_ipv4_addr ($subnet) )
					$subnet = '255.255.255.255';
			}
		} else
			$subnet = '255.255.255.255';

		// Nomalizing Network
		if ( is_numeric ($network) && $network > 16777215 ) {
			$network = long2ip ($network);
		} else if ( ! $ip->valid_ipv4_addr ($network) ) {
			if ( preg_match ('/^([0-9]{1,3}\.){2}[0-9]{1,3}\.$/', $network) ) {
				$network .= '0';
				$subnet = '255.255.255.0';
			} else if ( preg_match ('/^[0-9]{1,3}\.[0-9]{1,3}\.$/', $network) ) {
				$network .= '0.0';
				$subnet = '255.255.0.0';
			} else if ( preg_match ('/^[0-9]{1,3}\.$/', $network) ) {
				$network .= '0.0.0';
				$subnet = '255.0.0.0';
			}
		}

		$v = sprintf ('%s/%s', $network, $subnet);
	}
	// }}}

	// {{{ +-- static public (boolean) ip (&$v)
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
	static public function ip (&$v) {
		$v = preg_replace ('/[\s]+/', '', $v);
		$myip = WebAPI::client_ip ();

		if ( self::$ip === null )
			self::$ip = new IPCALC;

		$ip = &self::$ip;

		// range
		if ( preg_match ('/-/', $v) ) {
			$myip = ip2long ($myip);
			list ($start, $end) = preg_split ('/-/', $v);

			if ( ! $ip->valid_ipv4_addr ($start) )
				$start = 0;
			if ( ! $ip->valid_ipv4_addr ($end) )
				$end = 4294967295;

			$start = ip2long ($start);
			$end   = ip2long ($end);

			if ( $myip >= $start && $myip <= $end )
				return true;

			return false;
		}
		self::ip_nomalize ($v);

		list ($network, $subnet) = preg_split ('!/!', $v);

		$net = $ip->network ($network, $subnet);
		$netdiff = $ip->network ($myip, $subnet);

		if ( $net === $netdiff )
			return true;

		return false;
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
