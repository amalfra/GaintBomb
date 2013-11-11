﻿<?php
/**
 * GiantBomb PHP wrapper is a simple class written in PHP to
 * make interactions with GiantBomb api easier.
 *
 * @package    GiantBomb api PHP wrapper
 * @version    0.2
 * @author     Amal Francis
 * @author     Koroban
 * @license    MIT License
 */

class GiantBomb {

    /**
     * The api key
     *
     * @access private 
     * @type   string
     */
    private $api_key = "";
    
    /**
     * The api response type : json/xml
     *
     * @access private 
     * @type   string
     */
    private $resp_type = "json";
    
    /**
     * A variable to hold the formatted result of  
     * last api request
     *
     * @access public
     * @type   array
     */
    public $result = array();

    /**
     * api endpoint
     * prefix for all API cals
     *
     * @type   string
     */
    protected $endpoint = 'http://www.giantbomb.com/api/';

    /**
     * curl instance
     *
     * @type handle
     */
    protected $ch = null;

    /**
     * Constructor of class
     *
     * @param $key  string API key to use
     * @param $resp type   Type of respone to request
     *
     * @return void
     */
    function __construct($key, $resp = "json") {
        // Set the api key
        $this->api_key = $key;
        
        // Now set the api response type, Default to json
        $this->resp_type = $resp;

        // init new instance of curl
        $this->ch = curl_init();

        // set default options for curl
        curl_setopt_array($this->ch, array(
           CURLOPT_HEADER => false,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_NOPROGRESS => true,
           CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31'
        ));
    }

    /**
     * Destructor
     * deletes curl instance
     *
     * @return void
     */
    function __destruct() {
        // Close request to clear up some resources
        if (!is_null($this->ch)) {
            curl_close($this->ch);
        }
    }

    /**
     * send call to API
     *
     * @param $module string name of url suffix
     * @param $params array  get parameters to send to API
     *
     * @return array response of API
     */
    private function call($module, $params = array()) {
        // set api data
        $params['api_key'] = $this->api_key;
        $params['format']  = $this->resp_type;

        // build URL
        $url = $this->endpoint . $module . '?' . http_build_query($params);

        // Set URL 
        curl_setopt($this->ch, CURLOPT_URL, $url);

        // Send the request & save response to $resp
        $resp["data"] = curl_exec($this->ch);
        if (curl_errno($this->ch)) {
            throw new GiantBombException('API call failed: ' . curl_error($this->ch));
        }

        // save http response code
        $resp["httpCode"] = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        if (!$resp || !$resp["data"]) {
            throw new GiantBombException("Couldn't get information from API");
        }

        return $resp;
    }

    /**
     * format filter array to string
     *
     * @param $filters array list of filters
     *
     * @return string combined filter string
     */
    private function format_filter($filters) {
        $filters_merged = array();
        foreach ($filters as $ky => $vl) {
            $filters_merged[] = $ky . ':' . $vl;
        }

        return implode(',', $filters_merged);
    }

    /**
     * Get information about given object type
     *
     * @param  $id         string  ID to request
     * @param  $field_list array   list of fields to response
     *
     * @return array response
     */
    public function get_object($type, $id, $field_list = array()) {
        $resp = $this->call($type . '/' . $id . '/', array('field_list' => implode(',', $field_list)));

        // No game with given game id found
        if ($resp["httpCode"] == 404) {
            throw new GiantBombException('Couldn\'t find ' . $type . ' with game id "' . $id . '"');
        }

        return $this->parse_result($resp["data"]);
    }

    /**
     * Get list of objects by given filters
     *
     * @param $filter     array    filter by given values - no "," accepted
     * @param $limit      integer  limit result count by given limit
     * @param $offset     integer  offset of results
     * @param $platform   integer  ID of platform to limit
     * @param $sort       array    list of keys to sort, format key => asc/desc,
     * @param $field_list array    list of field to result
     *
     * @return array response
     */
    public function get_objects($type, $filter = array(), $limit = 100, $offset = 0, $platform = null, $sort = array(), $field_list = array()) {
        $resp = $this->call($type . '/', array(
            'field_list' => implode(',', $field_list),
            'limit' => $limit,
            'offset' => $offset,
            'platforms' => $platform,
            'sort' => $this->format_filter($sort),
            'filter' => $this->format_filter($filter)
        ));

        return $this->parse_result($resp["data"]);
    }

    /**
     * Get information about a game
     *
     * @param  $id         string  ID to request
     * @param  $field_list array   list of fields to response
     *
     * @return array response
     */
    public function game($id, $field_list = array()) {
        return $this->get_object('game', $id, $field_list);
    }
   
    /**
     * list games by filters
     *
     * @param $filter     array    filter by given values - no "," accepted
     * @param $limit      integer  limit result count by given limit
     * @param $offset     integer  offset of results
     * @param $platform   integer  ID of platform to limit
     * @param $sort       array    list of keys to sort, format key => asc/desc,
     * @param $field_list array    list of field to result
     *
     * @return array list of games
     */
    public function games($filter = array(), $limit = 100, $offset = 0, $platform = null, $sort = array(), $field_list = array()) {
        return $this->get_objects('games', $filter, $limit, $offset, $platform, $sort, $field_list);
    }

    /**
     * Get review by id
     *
     * @param  $id         string  ID to request
     * @param  $field_list array   list of fields to response
     *
     * @return array response
     */
    public function review($id, $field_list = array()) {
        return $this->get_object('review', $id, $field_list);
    }

    /**
     * Get game_rating by id
     *
     * @param  $id         string  ID to request
     * @param  $field_list array   list of fields to response
     *
     * @return array response
     */
    public function game_rating($id, $field_list = array()) {
        return $this->get_object('game_rating', $id, $field_list);
    }

    /**
     * Get company by id
     *
     * @param  $id         string  ID to request
     * @param  $field_list array   list of fields to response
     *
     * @return array response
     */
    public function company($id, $field_list = array()) {
        return $this->get_object('company', $id, $field_list);
    }

    /**
     * Get character by id
     *
     * @param  $id         string  ID to request
     * @param  $field_list array   list of fields to response
     *
     * @return array response
     */
    public function character($id, $field_list = array()) {
        return $this->get_object('character', $id, $field_list);
    }

    /**
     * return parsed result of api response
     *
     * @param $data string result of API
     *
     * @return mixed parsed version of input string
     */
    private function parse_result($data) {
        try {
            if ($this->resp_type == "json") {
                $result = @json_decode($data);
            } else {
                $result = @simplexml_load_string($data);
            }
        } catch (Exception $e) {
            throw new GiantBombException("Parse error occoured", null, $e);
        }

        if (empty($result) || !empty($result->error) && strtoupper($result->error) != "OK") {
            var_dump($data);
            throw new GiantBombException("Following error encountered: " . $result["error"]);
        }

        return $result;
    }
}

/**
 * Define a custom exception class for api wrapper
 */
class GiantBombException extends Exception {

    /**
     * Redefine the exception so message isn't optional
     *
     * @param $message  string    message to set in exception
     * @param $code     integer   error code
     * @param $previous Exception previous Exception to save
     *
     * @return void
     */
    public function __construct($message, $code = 0, Exception $previous = null)  {
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    /**
     * convert exception to string
     *
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
