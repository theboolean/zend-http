<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Cookie
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Http\Header;

use Zend\Uri;

/**
 * Zend_Http_Cookie is a class describing an HTTP cookie and all it's parameters.
 *
 * Zend_Http_Cookie is a class describing an HTTP cookie and all it's parameters. The
 * class also enables validating whether the cookie should be sent to the server in
 * a specified scenario according to the request URI, the expiry time and whether
 * session cookies should be used or not. Generally speaking cookies should be
 * contained in a Cookiejar object, or instantiated manually and added to an HTTP
 * request.
 *
 * See http://wp.netscape.com/newsref/std/cookie_spec.html for some specs.
 *
 * @category   Zend
 * @package    Zend_Http
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Cookie implements HeaderDescription
{
    /**
     * Cookie name
     *
     * @var string
     */
    protected $name;
    
    /**
     * Cookie value
     * 
     * @var string 
     */
    protected $value;

    /**
     * Cookie expiry date
     *
     * @var int
     */
    protected $expires = null;

    /**
     * Cookie domain
     *
     * @var string
     */
    protected $domain = null;

    /**
     * Cookie path
     *
     * @var string
     */
    protected $path = '/';

    /**
     * Whether the cookie is secure or not
     *
     * @var boolean
     */
    protected $secure = false;

    /**
     * Whether the cookie value has been encoded/decoded
     *
     * @var boolean
     */
    protected $encodeValue;

    /**
     * Cookie object constructor
     *
     * @todo Add validation of each one of the parameters (legal domain, etc.)
     *
     * @param string $name
     * @param string $value
     * @param string $domain
     * @param int $expires
     * @param string $path
     * @param bool $secure
     */
    public function __construct($name = null, $value = null, $domain = null, $expires = null, $path = null, $secure = false)
    {
        $this->type = 'Cookie';

        if ($name) {
            $this->setName($name);
        }

        if ($value) {
            $this->setValue($value); // in parent
        }

        if ($domain) {
            $this->setDomain($domain);
        }

        if ($expires) {
            $this->setExpires($expires);
        }

        if ($secure) {
            $this->setSecure($secure);
        }
    }

    public function getFieldName()
    {
        return 'Cookie';
    }

    public function getFieldValue()
    {
        return $this->__toString();
    }
    
    public function toString()
    {
        return 'Cookie: ' . $this->getFieldValue();
    }
    
    public function setName($name)
    {
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new Exception\InvalidArgumentException("Cookie name cannot contain these characters: =,; \\t\\r\\n\\013\\014 ({$name})");
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Get Cookie name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the cookie value
     * 
     * @param  string $value
     * @return Cookie 
     */
    public function setValue($value)
    {
        $this->value= $value;
        return $this;
    }
    /**
     * Get the cookie value
     * 
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Set the domain
     * 
     * @param  string $domain
     * @return Cookie 
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get cookie domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get the cookie path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the expiry time of the cookie, or null if no expiry time is set
     *
     * @return int|null
     */
    public function getExpiryTime()
    {
        return $this->expires;
    }

    /**
     * Set the expires time
     * 
     * @param string|int $expire
     * @return Cookie 
     */
    public function setExpires($expires)
    {
        if (!empty($expires)) {
            if (is_string($expires)) {
                $expires= strtotime($expires);
            } elseif (!is_int($expires)) {
                throw new Exception\InvalidArgumentException('Invalid expires time specified');
            }
            $this->expires= (int) $expires;
        }
        return $this;
    }
    /**
     * Check whether the cookie should only be sent over secure connections
     *
     * @return boolean
     */
    public function isSecure()
    {
        return $this->secure;
    }
    /**
     * Set secure
     * 
     * @param  boolean $secure
     * @return Cookie 
     */
    public function SetSecure($secure)
    {
        $this->secure= $secure;
        return $this;
    }
    /**
     * Check whether the cookie has expired
     *
     * Always returns false if the cookie is a session cookie (has no expiry time)
     *
     * @param int $now Timestamp to consider as "now"
     * @return boolean
     */
    public function isExpired($now = null)
    {
        if ($now === null) $now = time();
        if (is_int($this->expires) && $this->expires < $now) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check whether the cookie is a session cookie (has no expiry time set)
     *
     * @return boolean
     */
    public function isSessionCookie()
    {
        return ($this->expires === null);
    }

    /**
     * Checks whether the cookie should be sent or not in a specific scenario
     *
     * @param string|Uri\Uri $uri URI to check against (secure, domain, path)
     * @param boolean $matchSessionCookies Whether to send session cookies
     * @param int $now Override the current time when checking for expiry time
     * @return boolean
     */
    public function match($uri, $matchSessionCookies = true, $now = null)
    {
        if (is_string ($uri)) {
            $uri = Uri\UriFactory::factory($uri, 'http');
        }

        if (!$uri instanceof Uri\Uri) {
            throw new Exception\InvalidArgumentException('Invalid URI provided; does not implement Zend\Uri\Uri');
        }

        // Make sure we have a valid Zend_Uri_Http object
        $scheme = $uri->getScheme();
        if (! ($uri->isValid() && ($scheme == 'http' || $scheme =='https'))) {
            throw new Exception\InvalidArgumentException('Passed URI is not a valid HTTP or HTTPS URI');
        }

        // Check that the cookie is secure (if required) and not expired
        if ($this->secure && $scheme != 'https') {
            return false;
        }
        if ($this->isExpired($now)) {
            return false;
        }
        if ($this->isSessionCookie() && ! $matchSessionCookies) {
            return false;
        }

        // Check if the domain matches
        if (! self::matchCookieDomain($this->getDomain(), $uri->getHost())) {
            return false;
        }

        // Check that path matches using prefix match
        if (! self::matchCookiePath($this->getPath(), $uri->getPath())) {
            return false;
        }

        // If we didn't die until now, return true.
        return true;
    }

    /**
     * Get the cookie as a string, suitable for sending as a "Cookie" header in an
     * HTTP request
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->encodeValue) {
            return $this->name . '=' . urlencode($this->value) . ';';
        }
        return $this->name . '=' . $this->value . ';';
    }

    /**
     * Generate a new Cookie object from a cookie string
     * (for example the value of the Set-Cookie HTTP header)
     *
     * @param string $cookieStr
     * @param Uri\Uri|string $refUri Reference URI for default values (domain, path)
     * @param boolean $encodeValue Weither or not the cookie's value should be
     *                             passed through urlencode/urldecode
     * @return Cookie A new Cookie object or false on failure.
     */
    public static function fromString($cookieStr, $refUri = null, $encodeValue = true)
    {
        // Set default values
        if (is_string($refUri)) {
            $refUri = Uri\UriFactory::factory($refUri, 'http');
        }

        $name    = '';
        $value   = '';
        $domain  = '';
        $path    = '';
        $expires = null;
        $secure  = false;
        $parts   = explode(';', $cookieStr);

        // If first part does not include '=', fail
        if (strpos($parts[0], '=') === false) return false;

        // Get the name and value of the cookie
        list($name, $value) = explode('=', trim(array_shift($parts)), 2);
        $name  = trim($name);
        if ($encodeValue) {
            $value = urldecode(trim($value));
        }

        // Set default domain and path
        if ($refUri instanceof Uri\Uri) {
            $domain = $refUri->getHost();
            $path   = $refUri->getPath();
            $path   = substr($path, 0, strrpos($path, '/'));
        }

        // Set other cookie parameters
        foreach ($parts as $part) {
            $part = trim($part);
            if (strtolower($part) == 'secure') {
                $secure = true;
                continue;
            }

            $keyValue = explode('=', $part, 2);
            if (count($keyValue) == 2) {
                list($k, $v) = $keyValue;
                switch (strtolower($k))    {
                    case 'expires':
                        if(($expires = strtotime($v)) === false) {
                            /**
                             * The expiration is past Tue, 19 Jan 2038 03:14:07 UTC
                             * the maximum for 32-bit signed integer. Zend_Date
                             * can get around that limit.
                             */
                            $expireDate = new \Zend\Date\Date($v);
                            $expires = $expireDate->getTimestamp();
                        }
                        break;

                    case 'path':
                        $path = $v;
                        break;

                    case 'domain':
                        $domain = $v;
                        break;

                    default:
                        break;
                }
            }
        }

        if ($name !== '') {
            $ret = new self($name, $value, $domain, $expires, $path, $secure);
            $ret->encodeValue = ($encodeValue) ? true : false;
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * Check if a cookie's domain matches a host name.
     *
     * Used by Zend_Http_Cookie and Zend_Http_CookieJar for cookie matching
     *
     * @param  string $cookieDomain
     * @param  string $host
     *
     * @return boolean
     */
    public static function matchCookieDomain($cookieDomain, $host)
    {
        if (! $cookieDomain) {
            throw new Exception\InvalidArgumentException("\$cookieDomain is expected to be a cookie domain");
        }

        if (! $host) {
            throw new Exception\InvalidArgumentException("\$host is expected to be a host name");
        }

        $cookieDomain = strtolower($cookieDomain);
        $host = strtolower($host);

        if ($cookieDomain[0] == '.') {
            $cookieDomain = substr($cookieDomain, 1);
        }

        // Check for either exact match or suffix match
        return ($cookieDomain == $host ||
                preg_match("/\.$cookieDomain$/", $host));
    }

    /**
     * Check if a cookie's path matches a URL path
     *
     * Used by Zend_Http_Cookie and Zend_Http_CookieJar for cookie matching
     *
     * @param  string $cookiePath
     * @param  string $path
     * @return boolean
     */
    public static function matchCookiePath($cookiePath, $path)
    {
        if (! $cookiePath) {
            throw new Exception\InvalidArgumentException("\$cookiePath is expected to be a cookie path");
        }

        if ((null !== $path) && (!is_scalar($path) || is_numeric($path) || is_bool($path))) {
            throw new Exception\InvalidArgumentException("\$path is expected to be a cookie path");
        }
        $path = (string) $path;
        if (empty($path)) {
            $path = '/';
        }

        return (strpos($path, $cookiePath) === 0);
    }
}
