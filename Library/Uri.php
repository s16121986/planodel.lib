<?php

class Uri {

	const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
	const CHAR_GEN_DELIMS = ':\/\?#\[\]@';
	const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';
	const CHAR_RESERVED = ':\/\?#\[\]@!\$&\'\(\)\*\+,;=';

	/**
	 * Host part types represented as binary masks
	 * The binary mask consists of 5 bits in the following order:
	 * <RegName> | <DNS> | <IPvFuture> | <IPv6> | <IPv4>
	 * Place 1 or 0 in the different positions for enable or disable the part.
	 * Finally use a hexadecimal representation.
	 */
	const HOST_IPV4 = 0x01; //00001
	const HOST_IPV6 = 0x02; //00010
	const HOST_IPVFUTURE = 0x04; //00100
	const HOST_IPVANY = 0x07; //00111
	const HOST_DNS = 0x08; //01000
	const HOST_DNS_OR_IPV4 = 0x09; //01001
	const HOST_DNS_OR_IPV6 = 0x0A; //01010
	const HOST_DNS_OR_IPV4_OR_IPV6 = 0x0B; //01011
	const HOST_DNS_OR_IPVANY = 0x0F; //01111
	const HOST_REGNAME = 0x10; //10000
	const HOST_DNS_OR_IPV4_OR_IPV6_OR_REGNAME = 0x13; //10011
	const HOST_ALL = 0x1F; //11111

	protected $scheme;

	/**
	 * URI userInfo part (usually user:password in HTTP URLs)
	 *
	 * @var string
	 */
	protected $userInfo;

	/**
	 * URI hostname
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * URI port
	 *
	 * @var int
	 */
	protected $port;

	/**
	 * URI path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * URI query string
	 *
	 * @var string
	 */
	protected $query;

	/**
	 * URI fragment
	 *
	 * @var string
	 */
	protected $fragment;

	/**
	 * Which host part types are valid for this URI?
	 *
	 * @var int
	 */
	protected $validHostTypes = self::HOST_REGNAME;

	/**
	 * Array of valid schemes.
	 *
	 * Subclasses of this class that only accept specific schemes may set the
	 * list of accepted schemes here. If not empty, when setScheme() is called
	 * it will only accept the schemes listed here.
	 *
	 * @var array
	 */
	protected static $validSchemes = array();

	/**
	 * List of default ports per scheme
	 *
	 * Inheriting URI classes may set this, and the normalization methods will
	 * automatically remove the port if it is equal to the default port for the
	 * current scheme
	 *
	 * @var array
	 */
	protected static $defaultPorts = array();

	/**
	 * @var Escaper
	 */
	protected static $escaper;

	public function __construct($uri = null) {
		if (is_string($uri)) {
			$this->parse($uri);
		} elseif ($uri instanceof Uri) {
			// Copy constructor
			$this->setScheme($uri->getScheme());
			$this->setUserInfo($uri->getUserInfo());
			$this->setHost($uri->getHost());
			$this->setPort($uri->getPort());
			$this->setPath($uri->getPath());
			$this->setQuery($uri->getQuery());
			$this->setFragment($uri->getFragment());
		} elseif ($uri !== null) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Expecting a string or a URI object, received "%s"', (is_object($uri) ? get_class($uri) : gettype($uri))
			));
		}
	}

	/**
	 * Get the scheme part of the URI
	 *
	 * @return string|null
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * Get the User-info (usually user:password) part
	 *
	 * @return string|null
	 */
	public function getUserInfo() {
		return $this->userInfo;
	}

	/**
	 * Get the URI host
	 *
	 * @return string|null
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * Get the URI port
	 *
	 * @return int|null
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * Get the URI path
	 *
	 * @return string|null
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Get the URI query
	 *
	 * @return string|null
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Return the query string as an associative array of key => value pairs
	 *
	 * This is an extension to RFC-3986 but is quite useful when working with
	 * most common URI types
	 *
	 * @return array
	 */
	public function getQueryAsArray() {
		$query = array();
		if ($this->query) {
			parse_str($this->query, $query);
		}

		return $query;
	}

	/**
	 * Get the URI fragment
	 *
	 * @return string|null
	 */
	public function getFragment() {
		return $this->fragment;
	}

	public function setScheme($scheme) {
		if (($scheme !== null) && (!self::validateScheme($scheme))) {
			throw new Exception\InvalidUriPartException(sprintf(
				'Scheme "%s" is not valid or is not accepted by %s', $scheme, get_class($this)
			), Exception\InvalidUriPartException::INVALID_SCHEME);
		}

		$this->scheme = $scheme;
		return $this;
	}

	/**
	 * Set the URI User-info part (usually user:password)
	 *
	 * @param  string $userInfo
	 * @return Uri
	 * @throws Exception\InvalidUriPartException If the schema definition
	 * does not have this part
	 */
	public function setUserInfo($userInfo) {
		$this->userInfo = $userInfo;
		return $this;
	}

	/**
	 * Set the URI host
	 *
	 * Note that the generic syntax for URIs allows using host names which
	 * are not necessarily IPv4 addresses or valid DNS host names. For example,
	 * IPv6 addresses are allowed as well, and also an abstract "registered name"
	 * which may be any name composed of a valid set of characters, including,
	 * for example, tilda (~) and underscore (_) which are not allowed in DNS
	 * names.
	 *
	 * Subclasses of Uri may impose more strict validation of host names - for
	 * example the HTTP RFC clearly states that only IPv4 and valid DNS names
	 * are allowed in HTTP URIs.
	 *
	 * @param  string $host
	 * @throws Exception\InvalidUriPartException
	 * @return Uri
	 */
	public function setHost($host) {
		if (($host !== '') && ($host !== null) && !self::validateHost($host, $this->validHostTypes)
		) {
			throw new Exception\InvalidUriPartException(sprintf(
				'Host "%s" is not valid or is not accepted by %s', $host, get_class($this)
			), Exception\InvalidUriPartException::INVALID_HOSTNAME);
		}

		$this->host = $host;
		return $this;
	}

	/**
	 * Set the port part of the URI
	 *
	 * @param  int $port
	 * @return Uri
	 */
	public function setPort($port) {
		$this->port = $port;
		return $this;
	}

	/**
	 * Set the path
	 *
	 * @param  string $path
	 * @return Uri
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

	/**
	 * Set the query string
	 *
	 * If an array is provided, will encode this array of parameters into a
	 * query string. Array values will be represented in the query string using
	 * PHP's common square bracket notation.
	 *
	 * @param  string|array $query
	 * @return Uri
	 */
	public function setQuery($query) {
		if (is_array($query)) {
			// We replace the + used for spaces by http_build_query with the
			// more standard %20.
			$query = str_replace('+', '%20', http_build_query($query));
		}

		$this->query = $query;
		return $this;
	}

	/**
	 * Set the URI fragment part
	 *
	 * @param  string $fragment
	 * @return Uri
	 * @throws Exception\InvalidUriPartException If the schema definition
	 * does not have this part
	 */
	public function setFragment($fragment) {
		$this->fragment = $fragment;
		return $this;
	}

	protected function reset() {
		$this->setScheme(null);
		$this->setPort(null);
		$this->setUserInfo(null);
		$this->setHost(null);
		$this->setPath(null);
		$this->setFragment(null);
		$this->setQuery(null);
	}

	public function parse($uri) {
		$this->reset();

		// Capture scheme
		if (($scheme = self::parseScheme($uri)) !== null) {
			$this->setScheme($scheme);
			$uri = substr($uri, strlen($scheme) + 1);
		}

		// Capture authority part
		if (preg_match('|^//([^/\?#]*)|', $uri, $match)) {
			$authority = $match[1];
			$uri = substr($uri, strlen($match[0]));

			// Split authority into userInfo and host
			if (strpos($authority, '@') !== false) {
				// The userInfo can also contain '@' symbols; split $authority
				// into segments, and set it to the last segment.
				$segments = explode('@', $authority);
				$authority = array_pop($segments);
				$userInfo = implode('@', $segments);
				unset($segments);
				$this->setUserInfo($userInfo);
			}

			$nMatches = preg_match('/:[\d]{1,5}$/', $authority, $matches);
			if ($nMatches === 1) {
				$portLength = strlen($matches[0]);
				$port = substr($matches[0], 1);

				$this->setPort((int) $port);
				$authority = substr($authority, 0, -$portLength);
			}

			$this->setHost($authority);
		}

		if (!$uri) {
			return $this;
		}

		// Capture the path
		if (preg_match('|^[^\?#]*|', $uri, $match)) {
			$this->setPath($match[0]);
			$uri = substr($uri, strlen($match[0]));
		}

		if (!$uri) {
			return $this;
		}

		// Capture the query
		if (preg_match('|^\?([^#]*)|', $uri, $match)) {
			$this->setQuery($match[1]);
			$uri = substr($uri, strlen($match[0]));
		}
		if (!$uri) {
			return $this;
		}

		// All that's left is the fragment
		if ($uri && substr($uri, 0, 1) == '#') {
			$this->setFragment(substr($uri, 1));
		}

		return $this;
	}

	/**
	 * Compose the URI into a string
	 *
	 * @return string
	 * @throws Exception\InvalidUriException
	 */
	public function toString() {
		if (!$this->isValid()) {
			if ($this->isAbsolute() || !$this->isValidRelative()) {
				throw new Exception\InvalidUriException(
				'URI is not valid and cannot be converted into a string'
				);
			}
		}

		$uri = '';

		if ($this->scheme) {
			$uri .= $this->scheme . ':';
		}

		if ($this->host !== null) {
			$uri .= '//';
			if ($this->userInfo) {
				$uri .= $this->userInfo . '@';
			}
			$uri .= $this->host;
			if ($this->port) {
				$uri .= ':' . $this->port;
			}
		}

		if ($this->path) {
			$uri .= static::encodePath($this->path);
		} elseif ($this->host && ($this->query || $this->fragment)) {
			$uri .= '/';
		}

		if ($this->query) {
			$uri .= "?" . static::encodeQueryFragment($this->query);
		}

		if ($this->fragment) {
			$uri .= "#" . static::encodeQueryFragment($this->fragment);
		}

		return $uri;
	}

	/**
	 * Normalize the URI
	 *
	 * Normalizing a URI includes removing any redundant parent directory or
	 * current directory references from the path (e.g. foo/bar/../baz becomes
	 * foo/baz), normalizing the scheme case, decoding any over-encoded
	 * characters etc.
	 *
	 * Eventually, two normalized URLs pointing to the same resource should be
	 * equal even if they were originally represented by two different strings
	 *
	 * @return Uri
	 */
	public function normalize() {
		if ($this->scheme) {
			$this->scheme = static::normalizeScheme($this->scheme);
		}

		if ($this->host) {
			$this->host = static::normalizeHost($this->host);
		}

		if ($this->port) {
			$this->port = static::normalizePort($this->port, $this->scheme);
		}

		if ($this->path) {
			$this->path = static::normalizePath($this->path);
		}

		if ($this->query) {
			$this->query = static::normalizeQuery($this->query);
		}

		if ($this->fragment) {
			$this->fragment = static::normalizeFragment($this->fragment);
		}

		// If path is empty (and we have a host), path should be '/'
		// Isn't this valid ONLY for HTTP-URI?
		if ($this->host && empty($this->path)) {
			$this->path = '/';
		}

		return $this;
	}

	public function __toString() {
		try {
			return $this->toString();
		} catch (\Exception $e) {
			return '';
		}
	}

	public static function validateScheme($scheme) {
		if (!empty(static::$validSchemes) && !in_array(strtolower($scheme), static::$validSchemes)
		) {
			return false;
		}

		return (bool) preg_match('/^[A-Za-z][A-Za-z0-9\-\.+]*$/', $scheme);
	}

	/**
	 * Check that the userInfo part of a URI is valid
	 *
	 * @param  string $userInfo
	 * @return bool
	 */
	public static function validateUserInfo($userInfo) {
		$regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':]+|%[A-Fa-f0-9]{2})*$/';
		return (bool) preg_match($regex, $userInfo);
	}

	/**
	 * Validate the host part
	 *
	 * Users may control which host types to allow by passing a second parameter
	 * with a bitmask of HOST_* constants which are allowed. If not specified,
	 * all address types will be allowed.
	 *
	 * Note that the generic URI syntax allows different host representations,
	 * including IPv4 addresses, IPv6 addresses and future IP address formats
	 * enclosed in square brackets, and registered names which may be DNS names
	 * or even more complex names. This is different (and is much more loose)
	 * from what is commonly accepted as valid HTTP URLs for example.
	 *
	 * @param  string  $host
	 * @param  int $allowed bitmask of allowed host types
	 * @return bool
	 */
	public static function validateHost($host, $allowed = self::HOST_ALL) {
		/*
		 * "first-match-wins" algorithm (RFC 3986):
		 * If host matches the rule for IPv4address, then it should be
		 * considered an IPv4 address literal and not a reg-name
		 */
		if ($allowed & self::HOST_IPVANY) {
			if (static::isValidIpAddress($host, $allowed)) {
				return true;
			}
		}

		if ($allowed & self::HOST_REGNAME) {
			if (static::isValidRegName($host)) {
				return true;
			}
		}

		if ($allowed & self::HOST_DNS) {
			if (static::isValidDnsHostname($host)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate the port
	 *
	 * Valid values include numbers between 1 and 65535, and empty values
	 *
	 * @param  int $port
	 * @return bool
	 */
	public static function validatePort($port) {
		if ($port === 0) {
			return false;
		}

		if ($port) {
			$port = (int) $port;
			if ($port < 1 || $port > 0xffff) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate the path
	 *
	 * @param  string $path
	 * @return bool
	 */
	public static function validatePath($path) {
		$pchar = '(?:[' . self::CHAR_UNRESERVED . ':@&=\+\$,]+|%[A-Fa-f0-9]{2})*';
		$segment = $pchar . "(?:;{$pchar})*";
		$regex = "/^{$segment}(?:\/{$segment})*$/";
		return (bool) preg_match($regex, $path);
	}

	/**
	 * Check if a URI query or fragment part is valid or not
	 *
	 * Query and Fragment parts are both restricted by the same syntax rules,
	 * so the same validation method can be used for both.
	 *
	 * You can encode a query or fragment part to ensure it is valid by passing
	 * it through the encodeQueryFragment() method.
	 *
	 * @param  string $input
	 * @return bool
	 */
	public static function validateQueryFragment($input) {
		$regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':@\/\?]+|%[A-Fa-f0-9]{2})*$/';
		return (bool) preg_match($regex, $input);
	}

	/**
	 * URL-encode the user info part of a URI
	 *
	 * @param  string $userInfo
	 * @return string
	 * @throws Exception\InvalidArgumentException
	 */
	public static function encodeUserInfo($userInfo) {
		if (!is_string($userInfo)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Expecting a string, got %s', (is_object($userInfo) ? get_class($userInfo) : gettype($userInfo))
			));
		}

		$regex = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:]|%(?![A-Fa-f0-9]{2}))/';
		$escaper = static::getEscaper();
		$replace = function ($match) use ($escaper) {
			return $escaper->escapeUrl($match[0]);
		};

		return preg_replace_callback($regex, $replace, $userInfo);
	}

	/**
	 * Encode the path
	 *
	 * Will replace all characters which are not strictly allowed in the path
	 * part with percent-encoded representation
	 *
	 * @param  string $path
	 * @throws Exception\InvalidArgumentException
	 * @return string
	 */
	public static function encodePath($path) {
		if (!is_string($path)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Expecting a string, got %s', (is_object($path) ? get_class($path) : gettype($path))
			));
		}

		$regex = '/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/';
		$escaper = static::getEscaper();
		$replace = function ($match) use ($escaper) {
			return $escaper->escapeUrl($match[0]);
		};

		return preg_replace_callback($regex, $replace, $path);
	}

	/**
	 * URL-encode a query string or fragment based on RFC-3986 guidelines.
	 *
	 * Note that query and fragment encoding allows more unencoded characters
	 * than the usual rawurlencode() function would usually return - for example
	 * '/' and ':' are allowed as literals.
	 *
	 * @param  string $input
	 * @return string
	 * @throws Exception\InvalidArgumentException
	 */
	public static function encodeQueryFragment($input) {
		if (!is_string($input)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Expecting a string, got %s', (is_object($input) ? get_class($input) : gettype($input))
			));
		}

		$regex = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/';
		$escaper = static::getEscaper();
		$replace = function ($match) use ($escaper) {
			return $escaper->escapeUrl($match[0]);
		};

		return preg_replace_callback($regex, $replace, $input);
	}

	/**
	 * Extract only the scheme part out of a URI string.
	 *
	 * This is used by the parse() method, but is useful as a standalone public
	 * method if one wants to test a URI string for it's scheme before doing
	 * anything with it.
	 *
	 * Will return the scheme if found, or NULL if no scheme found (URI may
	 * still be valid, but not full)
	 *
	 * @param  string $uriString
	 * @throws Exception\InvalidArgumentException
	 * @return string|null
	 */
	public static function parseScheme($uriString) {
		if (!is_string($uriString)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Expecting a string, got %s', (is_object($uriString) ? get_class($uriString) : gettype($uriString))
			));
		}

		if (preg_match('/^([A-Za-z][A-Za-z0-9\.\+\-]*):/', $uriString, $match)) {
			return $match[1];
		}

		return null;
	}

	/**
	 * Remove any extra dot segments (/../, /./) from a path
	 *
	 * Algorithm is adapted from RFC-3986 section 5.2.4
	 * (@link http://tools.ietf.org/html/rfc3986#section-5.2.4)
	 *
	 * @todo   consider optimizing
	 *
	 * @param  string $path
	 * @return string
	 */
	public static function removePathDotSegments($path) {
		$output = '';

		while ($path) {
			if ($path == '..' || $path == '.') {
				break;
			}

			switch (true) {
				case ($path == '/.'):
					$path = '/';
					break;
				case ($path == '/..'):
					$path = '/';
					$output = substr($output, 0, strrpos($output, '/', -1));
					break;
				case (substr($path, 0, 4) == '/../'):
					$path = '/' . substr($path, 4);
					$output = substr($output, 0, strrpos($output, '/', -1));
					break;
				case (substr($path, 0, 3) == '/./'):
					$path = substr($path, 2);
					break;
				case (substr($path, 0, 2) == './'):
					$path = substr($path, 2);
					break;
				case (substr($path, 0, 3) == '../'):
					$path = substr($path, 3);
					break;
				default:
					$slash = strpos($path, '/', 1);
					if ($slash === false) {
						$seg = $path;
					} else {
						$seg = substr($path, 0, $slash);
					}

					$output .= $seg;
					$path = substr($path, strlen($seg));
					break;
			}
		}

		return $output;
	}

	/**
	 * Merge a base URI and a relative URI into a new URI object
	 *
	 * This convenience method wraps ::resolve() to allow users to quickly
	 * create new absolute URLs without the need to instantiate and clone
	 * URI objects.
	 *
	 * If objects are passed in, none of the passed objects will be modified.
	 *
	 * @param  Uri|string $baseUri
	 * @param  Uri|string $relativeUri
	 * @return Uri
	 */
	public static function merge($baseUri, $relativeUri) {
		$uri = new static($relativeUri);
		return $uri->resolve($baseUri);
	}

	/**
	 * Check if a host name is a valid IP address, depending on allowed IP address types
	 *
	 * @param  string  $host
	 * @param  int $allowed allowed address types
	 * @return bool
	 */
	protected static function isValidIpAddress($host, $allowed) {
		$validatorParams = array(
			'allowipv4' => (bool) ($allowed & self::HOST_IPV4),
			'allowipv6' => false,
			'allowipvfuture' => false,
			'allowliteral' => false,
		);

		// Test only IPv4
		$validator = new Validator\Ip($validatorParams);
		$return = $validator->isValid($host);
		if ($return) {
			return true;
		}

		// IPv6 & IPvLiteral must be in literal format
		$validatorParams = array(
			'allowipv4' => false,
			'allowipv6' => (bool) ($allowed & self::HOST_IPV6),
			'allowipvfuture' => (bool) ($allowed & self::HOST_IPVFUTURE),
			'allowliteral' => true,
		);
		static $regex = '/^\[.*\]$/';
		$validator->setOptions($validatorParams);
		return (preg_match($regex, $host) && $validator->isValid($host));
	}

	/**
	 * Check if an address is a valid DNS hostname
	 *
	 * @param  string $host
	 * @return bool
	 */
	protected static function isValidDnsHostname($host) {
		$validator = new Validator\Hostname(array(
			'allow' => Validator\Hostname::ALLOW_DNS | Validator\Hostname::ALLOW_LOCAL,
		));

		return $validator->isValid($host);
	}

	/**
	 * Check if an address is a valid registered name (as defined by RFC-3986) address
	 *
	 * @param  string $host
	 * @return bool
	 */
	protected static function isValidRegName($host) {
		$regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':@\/\?]+|%[A-Fa-f0-9]{2})+$/';
		return (bool) preg_match($regex, $host);
	}

	/**
	 * Part normalization methods
	 *
	 * These are called by normalize() using static::_normalize*() so they may
	 * be extended or overridden by extending classes to implement additional
	 * scheme specific normalization rules
	 */

	/**
	 * Normalize the scheme
	 *
	 * Usually this means simply converting the scheme to lower case
	 *
	 * @param  string $scheme
	 * @return string
	 */
	protected static function normalizeScheme($scheme) {
		return strtolower($scheme);
	}

	/**
	 * Normalize the host part
	 *
	 * By default this converts host names to lower case
	 *
	 * @param  string $host
	 * @return string
	 */
	protected static function normalizeHost($host) {
		return strtolower($host);
	}

	/**
	 * Normalize the port
	 *
	 * If the class defines a default port for the current scheme, and the
	 * current port is default, it will be unset.
	 *
	 * @param  int $port
	 * @param  string  $scheme
	 * @return int|null
	 */
	protected static function normalizePort($port, $scheme = null) {
		if ($scheme && isset(static::$defaultPorts[$scheme]) && ($port == static::$defaultPorts[$scheme])
		) {
			return null;
		}

		return $port;
	}

	/**
	 * Normalize the path
	 *
	 * This involves removing redundant dot segments, decoding any over-encoded
	 * characters and encoding everything that needs to be encoded and is not
	 *
	 * @param  string $path
	 * @return string
	 */
	protected static function normalizePath($path) {
		$path = self::encodePath(
				self::decodeUrlEncodedChars(
					self::removePathDotSegments($path), '/[' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]/'
				)
		);

		return $path;
	}

	/**
	 * Normalize the query part
	 *
	 * This involves decoding everything that doesn't need to be encoded, and
	 * encoding everything else
	 *
	 * @param  string $query
	 * @return string
	 */
	protected static function normalizeQuery($query) {
		$query = self::encodeQueryFragment(
				self::decodeUrlEncodedChars(
					$query, '/[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]/'
				)
		);

		return $query;
	}

	/**
	 * Normalize the fragment part
	 *
	 * Currently this is exactly the same as normalizeQuery().
	 *
	 * @param  string $fragment
	 * @return string
	 */
	protected static function normalizeFragment($fragment) {
		return static::normalizeQuery($fragment);
	}

	/**
	 * Decode all percent encoded characters which are allowed to be represented literally
	 *
	 * Will not decode any characters which are not listed in the 'allowed' list
	 *
	 * @param string $input
	 * @param string $allowed Pattern of allowed characters
	 * @return mixed
	 */
	protected static function decodeUrlEncodedChars($input, $allowed = '') {
		$decodeCb = function ($match) use ($allowed) {
			$char = rawurldecode($match[0]);
			if (preg_match($allowed, $char)) {
				return $char;
			}
			return strtoupper($match[0]);
		};

		return preg_replace_callback('/%[A-Fa-f0-9]{2}/', $decodeCb, $input);
	}

}
