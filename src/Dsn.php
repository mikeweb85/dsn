<?php

namespace MikeWeb\Dsn;

/**
 * Parse a DSN string to get its parts.
 * 
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Mike Web <mweb@mikeweb.ninja>
 * 
 * multi protocol
 * (!,\s)?(?P<_protocol>(?P<protocol>[\w\\\\]+):\/\/)(?P<host>[^?#\/:@,;\s]+)(?::(?P<port>\d+))?(?P<_path>(?P<path>\/[^?#,\s]*))?(?P<_query>\?(?P<query>[^#,\s]*))?
 * 
 */
final class Dsn {
    /**
     * @var string
     */
    private $dsn;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var array
     */
    private $authentication;

    /**
     * @var array
     */
    private $hosts;

    /**
     * @var string
     */
    private $database;
    
    /**
     * @var string
     */
    private $table;
    
    /**
     * @var string
     */
    private $fragment;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * Constructor.
     *
     * @param string $dsn
     */
    public function __construct($dsn) {
        $this->dsn = $dsn;
        $this->parseDsn($dsn);
    }

    /**
     * @return string
     */
    public function getDsn() {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * @return string|null
     */
    public function getDatabase() {
        return $this->database;
    }
    
    /**
     * @return string|null
     */
    public function getTable() {
        return $this->table;
    }
    
    /**
     * @return string|null
     */
    public function getFragment() {
        return $this->fragment;
    }

    /**
     * @return array
     */
    public function getHosts() {
        return $this->hosts;
    }

    /**
     * @return null|string
     */
    public function getFirstHost() {
        return $this->hosts[0]['host'];
    }

    /**
     * @return null|int
     */
    public function getFirstPort() {
        return $this->hosts[0]['port'];
    }

    /**
     * @return array
     */
    public function getAuthentication() {
        return $this->authentication;
    }

    public function getUsername() {
        return $this->authentication['username'];
    }

    public function getPassword() {
        return $this->authentication['password'];
    }

    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function isValid() {
        if (null === $this->getProtocol()) {
            return false;
        }

        if (empty($this->getHosts())) {
            return false;
        }

        return true;
    }

    /**
     * @param string $dsn
     */
    private function parseDsn($dsn) {
        // Parse protocol and auth
        $matches = [];
        $regex = '/^(?P<_protocol>(?P<protocol>[\w\\\\]+):\/\/)(?P<_username>(?P<username>.*?)(?P<_password>:(?P<password>.*?))?@)?/i';
        
        preg_match($regex, $dsn, $matches);
        
        if ( empty($matches['protocol']) ) {
            return;
        }
        
        $this->protocol = $matches['protocol'];
        
        $this->authentication = [
            'username'  => isset($matches['username']) ? $matches['username'] : null,
            'password'  => isset($matches['password']) ? $matches['password'] : null,
        ];

        // Remove the protocol and auth
        $dsn = str_replace($matches[0], '', $dsn);
        
        // Parse path (database/table), parameters, and fragment
        $matches = [];
        $regex = '/(?P<_path>(?P<path>\/[^?#]*))?(?P<_query>\?(?P<query>[^#]*))?(?P<_fragment>\#(?P<fragment>.*))?$/i';
        
        preg_match($regex, $dsn, $matches);
        
        if ( !empty($matches['path']) ) {
            $temp = explode('/', ltrim($matches['path'],'/'));
            $this->database = $temp[0];
            $this->table = isset($temp[1]) ? $temp[1] : null;
        }
        
        if ( !empty($matches['query']) ) {
            $this->parameters = $this->parseParameters($matches['query']);
        }
        
        if ( !empty($matches['fragment']) ) {
            $this->fragment = $matches['fragment'];
        }

        // Remove path, params, and fragment
        $dsn = str_replace($matches[0], '', $dsn);
        
        $this->parseHosts($dsn);
    }

    private function parseHosts($hostString) {
        $hosts = $matches = [];
        
        //preg_match_all('/(?P<host>[^?#\/:@,;]+)(?::(?P<port>\d+))?/mi', $hostString, $matches);
        preg_match_all('/(![,\s\(])?(?P<_protocol>(?P<protocol>[\w\\\\]+):\/\/)?(?P<host>[^?#\/:@,;\s\(\)]+)(?::(?P<port>\d+))?(?P<_query>\?(?P<query>[^#,\s\)]*))?/i', $hostString, $matches);
        
        foreach ($matches['host'] as $index => $match) {
            $hosts[$index] = [
                'host'  => $match,
                'port'  => !empty($matches['port'][$index]) ? (int) $matches['port'][$index] : null,
            ];
            
            if ( !empty($match['protocol'][$index]) ) {
                $hosts[$index]['protocol'] = $match['protocol'][$index];
            }
            
            if ( !empty($matches['query'][$index]) ) {
                $hosts[$index]['parameters'] = $this->parseParameters($matches['query'][$index]);
            }
        }

        $this->hosts = $hosts;
    }
    
    private function parseParameters(string $queryString) {
        $parameters = [];
        
        if ( !empty($queryString) ) {
            parse_str($queryString, $parameters);
            
            foreach ($parameters as $key=>$value) {
                if ($value === 'true') {
                    $parameters[$key] = true;
                    
                } elseif ($value === 'false') {
                    $parameters[$key] = false;
                    
                } elseif ($value === 'null' || $value == '') {
                    $parameters[$key] = null;
                }
            }
        }
        
        return $parameters;
    }
}
