<?php
/**
 * Project: WebAPI_Browser :: Get browser information
 * File:    WebAPI/WebAPI_Browser.php
 *
 * WebAPI_Browser class는 Browser의 정보를 구한다.
 *
 * @category    HTTP
 * @package     WebAPI
 * @subpackage  WebAPI_Browser
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 2013, JoungKyun.Kim
 * @license     BSD License
 * @version     $Id$
 * @link        http://pear.oops.org/package/WebAPI
 * @filesource
 * @since       1.0.2
 */

/**
 * Get browser information
 *
 * WebAPI_Browser class는 Browser의 정보를 구한다.
 *
 * @package     WebAPI
 */
Class WebAPI_Browser {
	/**
	 * 브라우저 정보를 구함
	 *
	 * @access public
	 * @param string (optional) User Agent를 강제로 지정할 경우 사용
	 * @return stdClass
	 */
	static public function exec ($u = null) {
		if ( $u )
			$_SERVER['HTTP_USER_AGENT'] = $u;
		$ua = &$_SERVER['HTTP_USER_AGENT'];

		$br = self::initVar ();

		self::OS ($br, $ua);
		self::LANGUAGE ($br, $ua);
		self::isMobile ($br, $ua);

		if ( preg_match ('/Konqueror/', $ua) ) {
			$br->name = 'Konqueror';
			$br->engine = $br->name;
			if ( preg_match ('/Konqueror\/([0-9]+(\.[0-9]+)?)/', $ua, $m) )
				$br->version = $m[1];
		}

		else if ( preg_match ('/Chrome|CriOS|CrMo/', $ua) ) {
			$br->name = 'Chrome';
			if ( preg_match ('/(Chrome|CriOS|CrMo)\/([0-9]+(\.[0-9]+)?)/', $ua, $m) ) {
				$br->version = $m[2];
				$br->engine = ($m[2] > 27) ? 'Blink' : 'WebKit';
			}
		}

		else if ( preg_match ('/Safari|AppleWebKit/', $ua) ) {
			$br->name = 'Safari';
			$br->engine = 'WebKit';
			if ( preg_match ('/Safari\/([0-9]+(\.[0-9]+)?)/', $ua, $m) )
				$br->version = $m[1];
		}

		else if ( preg_match ('/KHTML/', $ua) ) {
			$br->name = 'KHTML';
			$br->engine = $br->name;
			if ( preg_match ('/KHTML\/([0-9]+(\.[0-9]+)?)/', $ua, $m) )
				$br->version = $m[1];
		}

		else if ( preg_match ('/BlackBerry/', $ua) ) {
			$br->name = 'BlackBerry';
			$br->engine = 'BlackBerry';
		}

		else if ( preg_match ('/MSIE|Trident/', $ua) ) {
			$br->name = 'IE';
			$br->engine = 'IE';

			if ( preg_match ('/MSIE ([0-9]+(\.[0-9]+)?)/', $ua, $m) ) {
				$br->version = $m[1];

				if ( $br->ostype == 'Mac' && $br->version > 4 )
					$br->engine = 'Tasman';
				else if ( $br->version > 3 )
					$br->engine = 'Trident';
			} else {
				if ( preg_match ('/rv:([0-9]+(\.[0-9]+)?)/', $ua, $m) )
					$br->version = $m[1];
				$br->engine = 'Trident';
			}
		}

		else if ( preg_match ('/Netscape|Mozilla\/[1-4]\.([1-9]|[0-9][1-9])/i', $ua) ) {
			$br->name = 'Netscase';
			if ( preg_match ('/Gekco/', $ua) )
				$br->engine = 'Gekco';
			else
				$br->engine = 'Netscape';

			if ( preg_match ('/Netscape[0-9]?\/([0-9]+(\.[0-9]+)?)/', $ua, $m) ) {
				$br->version = $m[1];
			}
		}

		else if ( preg_match ('/Gecko|Galeon/i', $ua) ) {
			if ( preg_match ('/Firefox/', $ua) )
				$br->name = 'Firefox';
			else
				$br->name = 'Mozilla';

			if ( $br->name == 'Mozilla' && preg_match ('/Thunderbird/', $ua) )
				$br->name = 'Thunderbird';

			$br->engine = 'Gecko';

			switch ($br->name) {
				case 'Firefox' :
				case 'Thunderbird' :
					if ( preg_match ('/(Firefox|Thunderbird)\/([0-9]+(\.[0-9]+(\.[0-9]+)?)?)/', $ua, $m) )
						$br->version = $m[2];
					break;
				default :
					if ( preg_match ('/rv:([0-9]+(\.[0-9]+(\.[0-9]+)?)?)/', $ua, $m) )
						$br->version = $m[1];
			}
		}

		else if ( preg_match ('/(Lynx|w3m|Links)/i', $ua, $m) ) {
			$br->name = $m[1];
			$br->engine = $br->name;
			$br->text = true;
		}

		else if ( preg_match ('/Opera/', $ua) ) {
			$br->name = 'Opera';

			// version
			// 7~14 Presto
			// 15+ Blink
			if ( preg_match ('/Opera\/([0-9.]+)/', $ua, $m) ) {
				$br->version = $m[1];

				if ( $br->version > 6 && $br->version < 15 )
					$br->engine = 'Presto';
				else
					$br->engine = 'Blink';
			} else {
				if ( preg_match ('/Presto/', $ua) )
					$br->engine = 'Presto';
				else
					$br->engine = 'Blink';
			}
		}

		return $br;
	}

	/**
	 * 브라우저 OS 정보
	 *
	 * @access public
	 */
	static public function OS (&$v, $ua = null) {
		if ( $ua == null )
			$ua = &$_SERVER['HTTP_USER_AGENT'];

		if ( preg_match ('/Linux|Android|J2ME/', $ua) )
			self::LINUX ($v);
		else if ( preg_match ('/Mac|iPhone|iPad/', $ua) )
			self::MAC ($v);
		else if ( preg_match ('/BlackBerry/', $ua) )
			self::BLACKBERRY ($v);
		else if ( preg_match ('/Win/', $ua) )
			self::WINDOWS ($v);
		else if ( preg_match ('/RIM Tablet OS ([0-9.]+)/', $ua, $m) ) {
			$v->os = 'RIM' + $m[1];
			$v->ostype = 'RIM';
		} else if ( preg_match ('/SymOS/', $ua) ) {
			$v->os = 'Symbian';
			$v->ostype = 'Nokia';
		}
	}

	/**
	 * 브라우저 언어 정보
	 *
	 * @access public
	 */
	static public function LANGUAGE (&$v, $ua = null) {
		if ( $ua == null )
			$ua = &$_SERVER['HTTP_USER_AGENT'];

		if ( ! preg_match ('/([a-z]{2})[-_]([a-zA-Z]{2})/', $ua, $m) ) {
			if ( preg_match ('/([a-z]{2}); rv/i', $ua, $m) )
				if ( $m[1] != 'le' )
					$v->lang = $m[1];
			return;
		}

		if ( $m[1] != 'le' )
			$v->lang = $m[1];
	}

	/**
	 * 모바일 브라우저 여부
	 *
	 * @access public
	 */
	static public function isMobile (&$v, $ua = null) {
		if ( $ua == null )
			$ua = &$_SERVER['HTTP_USER_AGENT'];

		if ( ! isset ($v->mobile) )
			$v->mobile = false;

		$re =
			'/Mobile|iPhone|iPad|Android|BlackBerry| RIM |' .
			'Tablet|Fennec|Opera mobi|dows CE|HTC/i';

		if ( preg_match ($re, $ua) )
			$v->mobile = true;

		if ( ! $v->mobile && preg_match ('/Android|iOS|Symbian/i', $ua) )
			$v->mobile = true;

		if ( $v->mobile === true && preg_match ('/Windows/', $v->ostype) )
			$v->ostype = 'Windows Mobile';
	}

	// {{{ +-- private (void) initVar (&$br)
	/**
	 * 변수 초기화
	 *
	 * @access private
	 * @param stdClass 초기화할 stdClass property
	 */
	static public function initVar () {
		return (object) array (
			'name'    => 'Unknwon',
			'os'      => 'Unknwon',
			'ostype'  => 'Unknown',
			'lang'    => 'en',
			'version' => 'Unknown',
			'engine'  => 'Unknwon',
			'text'    => false,
			'mobile'  => false
		);
	}
	// }}}

	// {{{ +-- private WINDOWS (&$v)
	private function WINDOWS (&$v, $ua = null) {
		if ( $ua == null )
			$ua = &$_SERVER['HTTP_USER_AGENT'];

		# Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko

		// Firefox Mobile
		if ( preg_match ('/Fennec/', $ua) ) {
			preg_match ('/NT ([0-9])/', $ua);
			$v->os = 'CE';
			$v->ostype = 'Windows Mobile';
			return;
		}

		if ( preg_match ('/Windows (Phone|CE)/', $ua) ) {
			if ( preg_match ('/Windows Phone (OS )?([0-9.]+)/', $ua, $m) )
				$v->os = 'Phone ' + $m[2];
			else if ( preg_match ('/Phone|CE/', $ua, $m) )
				$v->os = $m[0];
			else
				$v->os = 'Windows';

			$v->ostype = 'Windows';
			return;
		}

		if ( preg_match ('/NT/', $ua) ) {
			preg_match ('/NT ([\d]+\.[\d]+)/', $ua, $m);

			switch ($m[1]) {
				case '5.0' :
					$v->os = '2000';
					break;
				case '5.1' :
					$v->os = 'XP';
					break;
				case '5.2' :
					$v->os = '2003';
					break;
				case '6.0' :
					$v->os = 'Vista';
					break;
				case '6.1' :
					$v->os = '7';
					break;
				case '6.2' :
					$v->os = '8';
					break;
				case '6.3' :
					$v->os = '8.1';
					break;
				default :
					$v->os = 'NT';
			}
			$v->ostype = 'Windows NT';

			return;
		}

		if ( preg_match ('/Win/', $ua) ) {
			$v->os = 'Windows';
			$v->ostype = 'Windows';
			return;
		}

		$v->os = 'Windows ?';
		$v->ostype = 'Windows';
	}
	// }}}

	// {{{ +-- private LINUX (&$v)
	private function LINUX (&$v, $ua = null) {
		if ( $ua == null )
			$ua = &$_SERVER['HTTP_USER_AGENT'];
		$v->ostype = 'Linux';

		if ( preg_match ('/Android[- ][0-9].[0-9]/', $ua, $m) ) {
			$v->os = preg_replace ('/roid /', 'roid-', $m[0]);
			return;
		} else if ( preg_match ('/Android|J2ME/', $ua, $m) ) {
			$v->os = 'Android';
			return;
		}

		if ( preg_match ('/Linux ([0-9]+\.[0-9]+)/', $ua, $m) )
			$v->os = $m[0];
		else
			$v->os = 'Linux';

		if ( preg_match ('/Ubuntu/', $ua) )
			$v->os = 'Ubuntu';
		else if ( preg_match ('/armv/', $ua) )
			$v->os = 'Android';
	}
	// }}}

	// {{{ +-- private MAC (&$v)
	private function MAC (&$v, $ua = null) {
		if ( $ua == null )
			$ua = &$_SERVER['HTTP_USER_AGENT'];

		if ( preg_match ('/iPhone|iPad/', $ua, $m) ) {
			$v->ostype = $m[0];
			if ( preg_match ('/OS ([0-9]+)/', $ua, $m) )
				$v->os = 'iOS' + $m[1];
			else
				$v->os = 'iOS';
		} else {
			$v->os = 'Mac OS';
			$v->ostype = 'Mac';
		}
	}
	// }}}

	// {{{ +-- private BLACKBERRY (&$v)
	private function BLACKBERRY (&$v) {
		$ua = &$_SERVER['HTTP_USER_AGENT'];

		$ua = preg_replace ('/[^0-9]+\//', '/', $ua);
		if ( preg_match ('/BlackBerry([0-9]+)\/([0-9]+\.[0-9]+)/', $ua, $m) ) {
			$v->os = 'BlackBerry' . $m[2];
			$v->version = $m[1];
		} else
			$v->os = 'BlackBerry';
		$v->ostype = 'BlackBerry';
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
