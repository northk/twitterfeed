<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
require_once(PATH_THIRD . 'twitterfeed/config.php');

/**
 * Twitterfeed cache and storage/retrieval Classes for EE2
 *
 * @package   Twitterfeed
 * @author     North Krimsly <north@highintegritydesign.com>
 * @copyright Copyright (c) 2011 High Integrity Design LLC
 * @license http://www.opensource.org/licenses/mit-license.php
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT 
 * SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE 
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */

class Twitterfeed_tweets
{
	private $_screen_name = NULL;
	private $_feed_data = NULL; 
	private $_feed_timestamp = NULL;
	private $_numtweets = 0;
	private $_cache_refresh = 0;
	private $_include_rts = NULL;


/** --------------------------------------------------
 *   Replace any URL's with an anchor link, and replace '@usernames' 
 *   and '#hashtags' in the tweet with their appropriate URL's.
 *   @return the tweet text with username and hashtags replaced
 *   --------------------------------------------------*/ 
    public function format_tweet($tweet)
    {
        // RegExp to replace all URL's with an anchor link, used with permission from
        // http://daringfireball.net/2010/07/improved_regex_for_matching_urls.
        // Convert output to UTF-8. Otherwise we'll get wierd characters printed out.
        $tweet = htmlentities($tweet, ENT_QUOTES, 'UTF-8');
        $pattern = '`(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))`si';
        $replacement = '<a href="$1" rel="nofollow">$1</a>';
        $tweet = preg_replace($pattern, $replacement, $tweet);
        
        // Next two RegExp's match all occurances of @ (Twitter user names) and # (Twitter hash tag).
        // Used with permission from http://granades.com/2009/04/06/using-regular-expressions-to-match-twitter-users-and-hashtags/
        
        // look for '@username' and turn it into a twitter user link
        $pattern = '/(^|\s)@(\w+)/';        
        $replacement = '\1<a href="http://www.twitter.com/\2">@\2</a>';
        $tweet = preg_replace($pattern, $replacement, $tweet);
    
        // look for '#' and turn it into a Twitter hash tag
        $pattern = ('/(^|\s)#(\w+)/');
        $replacement = '\1<a href="http://search.twitter.com/search?q=%23\2">#\2</a>';
        $tweet = preg_replace($pattern, $replacement, $tweet);

        $tweet = xss_clean($tweet);                    
        return $tweet;                    
    }

 
/** --------------------------------------------------
 *   Convert the tweet date/time into a unix timestamp.
 *   Not using htmlentities() or xss_clean() here since strtotime() will simply return the current
 *   date and time if it gets bad input.
 *   @return the formatted date/time which is automatically parsed by EE's template engine
 *   --------------------------------------------------*/ 
    public function format_date($twitter_time)
    {
		$unix_timestamp = strtotime($twitter_time);
		return $unix_timestamp;
    }


/** --------------------------------------------------
 *   Clean data for output. This function is used whenever the output needs to be cleaned but no
 *   additional work needs to be done to format the data for output.
 *   @return the data, cleaned for output
 *   --------------------------------------------------*/ 
    public function clean_output($suspect_output)
    {
        $clean_output = htmlentities($suspect_output, ENT_QUOTES, 'UTF-8');
        $clean_output = xss_clean($clean_output);
        return $clean_output;
    }


/** --------------------------------------------------
 *   Iterate through all of the tweets, formatting each of them for display. Give the output to EE to parse.
 *   @return the finished HTML output that EE's template engine will use or FALSE if data couldn't be obtained
 *   --------------------------------------------------*/ 
    public function get_tweets()
    {
    
        // if there's no feed data (because the cache read was unsuccessful and the Twitter API had an error),
        // then return FALSE.
        if ($this->_feed_data === NULL)
        {
            return FALSE;
        }
        else
        {
            $tweets = NULL;
            $count = 0;
            foreach ($this->_feed_data->status as $status) 
            {
                if ($count < $this->_numtweets)
                {
                    $variables[] = array('screen_name' => $this->clean_output($status->user->screen_name),
                                                  'name' => $this->clean_output($status->user->name),
                                                  'created_at' => $this->format_date($status->created_at),
                                                  'profile_image_url' => $this->clean_output($status->user->profile_image_url), 
                                                  'location' => $this->clean_output($status->user->location),
                                                  'description' => $this->clean_output($status->user->description),
                                                  'url' => $this->clean_output($status->user->url),
                                                  'text' => $this->format_tweet($status->text));
                    $count++;                                                  
                }                                            
            }
            $output = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $variables);
            return $output;
		}
    }
	
	
/** --------------------------------------------------
 *   Constructor
 *   --------------------------------------------------*/
    function __construct($screen_name, $numtweets, $cache_refresh, $include_rts)
    {
        $this->EE =& get_instance();
        
        $this->_screen_name = $screen_name;
        $this->_numtweets = $numtweets;
        $this->_cache_refresh = $cache_refresh;
        $this->_include_rts = $include_rts;
        
        // if there's no data in the cache for the screen_name, get a new feed from Twitter and update the cache.
        // if we call Twitter and the Twitter API call then fails, there's nothing else we can do!
        if ($this->read_cache() === FALSE)
        {
            if ($this->get_feed() === TRUE)
            {
                $this->write_cache();
            }
            else
            {
                $this->EE->TMPL->log_item('Twitterfeed module : no data in cache for screen name (or cannot read cache) and bad results from Twitter API');  
            }
        }
        
        // else there is data in the cache for the screen_name, so check and see if it's too old to use or if there isn't enough 
        // data to meet the limit="" tag parameter requirement.
        // 
        // if it's too old or there isn't enough data, call Twitter and see if we can get new data. 
		// If the Twitter API call succeeds then update the cache. 
        //
        // If the cache is still fresh or the Twitter API call failed, use the cached data-- 
		// even if it's stale or there aren't enough tweets in it to match the limit="" tag.
        else
        {
            $cached_statuses_count = count($this->_feed_data->status);
            
            if (($cached_statuses_count < $this->_numtweets) || ($this->cache_is_stale() === TRUE))
            {
                if ($cached_statuses_count < $this->_numtweets)
                {
                    $this->EE->TMPL->log_item('Twitterfeed module : cache contains less than the desired number of tweets, attempting to retrieve new feed');
                }
                if ($this->get_feed() === TRUE)
                {
                    $this->write_cache();
                }
            }
        }
    }
		

/** --------------------------------------------------
 *   Check if (right now - last update) > max allowed cache interval. If so, cache is stale.
 *   @return TRUE if cache is stale, FALSE if cache is fresh
 *   --------------------------------------------------*/
	private function cache_is_stale()
	{
 	    $now = time();
        $timespan = $now - $this->_feed_timestamp;
        
        if ($timespan > ($this->_cache_refresh * 60))
        {
            $this->EE->TMPL->log_item('Twitterfeed module : cache is stale');   
            return TRUE;
        }
        else
        {
            $this->EE->TMPL->log_item('Twitterfeed module : cache is fresh. Will use existing data from cache unless there\'s not enough');   
            return FALSE;
        }
	}


/** --------------------------------------------------
 *   Read the cache.
 *   @return TRUE if success, FALSE if no data in cache for the given screen_name
 *   --------------------------------------------------*/
    public function read_cache()
	{
		$this->EE->db->select('feed_data, feed_timestamp');
		$this->EE->db->where('screen_name', $this->_screen_name);
		$query = $this->EE->db->get(Twitterfeed_config::TWITTERFEED_TABLE);
		if ($query->num_rows() == 0)
		{
            $this->EE->TMPL->log_item('Twitterfeed module : no data found in cache for screen name : ' . $this->clean_output($this->_screen_name));   		
			return FALSE;
		}
		else
		{
            try
            {
                $xml = new SimpleXMLElement($query->row()->feed_data, LIBXML_NOWARNING | LIBXML_NOERROR); 
            }

            catch (Exception $e)
            {
                $this->EE->TMPL->log_item('Twitterfeed module : invalid XML returned from cache');   
                return FALSE;
            }
		
			$this->_feed_data = $xml;
			$this->_feed_timestamp = $query->row()->feed_timestamp;
            $this->EE->TMPL->log_item('Twitterfeed module : successfully read from cache');   
    		return TRUE;		
		}
	}
	
	
/** --------------------------------------------------
 *   Write the cache. Perform either an insert or an update on the database
 *   @return no return value.
 *   --------------------------------------------------*/
	public function write_cache()
	{
		$this->EE->db->select('screen_name');
		$this->EE->db->where('screen_name', $this->_screen_name);
		$query = $this->EE->db->get(Twitterfeed_config::TWITTERFEED_TABLE);
		if ($query->num_rows() == 0)
		{
			$this->EE->db->insert(Twitterfeed_config::TWITTERFEED_TABLE, array('screen_name' => $this->_screen_name, 
														'feed_data' => $this->_feed_data->asXML(), 
														'feed_timestamp' => $this->_feed_timestamp));
		}
		else
		{
			$this->EE->db->where('screen_name', $this->_screen_name);
			$this->EE->db->update(Twitterfeed_config::TWITTERFEED_TABLE, array('feed_data' => $this->_feed_data->asXML(),
														'feed_timestamp' => $this->_feed_timestamp));
		}
        $this->EE->TMPL->log_item('Twitterfeed module : wrote new data to cache');   
	}
	
	
/** --------------------------------------------------
 *   Query the Twitter REST API using cURL and request a user timeline feed for the given screen_name
 *   @return TRUE if feed was read and a valid SimpleXMLElement was created; otherwise FALSE
 *   --------------------------------------------------*/
    public function get_feed()
    {
        $request = 'http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=' . 
        urlencode($this->_screen_name) . '&count=' . urlencode((string) $this->_numtweets);
        
        // include retweets if asked for
        if ($this->_include_rts == "yes")
        {
            $request = $request . "&include_rts=1";
        }
        else
        {
            $request = $request . "&include_rts=0";        
        }

        
        $curl = curl_init($request);
        
        // set result to be a string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 

        // do not include the HTTP header in the result
        curl_setopt($curl, CURLOPT_HEADER, 0); 
        
        // fail if error 400 or greater is encountered        
        curl_setopt($curl, CURLOPT_FAILONERROR, 0); 
        
        // set timeout        
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, Twitterfeed_config::TWITTERFEED_CURL_TIMEOUT); 
        $result = curl_exec($curl);
        curl_close($curl);

        if ($result === FALSE)
        {
			$this->EE->TMPL->log_item('Twitterfeed module : cURL exec error : ' . curl_error($curl));     
			return FALSE;
        }
        
        // create a traversable XML object form the Twitter result.
        // if there was an error returned from Twitter, log it.
        // or else store the new feed data and timestamp.
        try
		{
    		$xml = @new SimpleXMLElement($result, LIBXML_NOWARNING | LIBXML_NOERROR); 

		}
		catch (Exception $e)
		{
			$this->EE->TMPL->log_item('Twitterfeed module : invalid XML returned from Twitter API');   
			return FALSE;
		}
		
		// load up all the children so we can check for an HTML error page returned by Twitter
		$nodes = $xml->children(); 
            
            // first check for a rate limit error or other error condition that returns a proper <error> node
		if ($xml->error) 
		{ 
			$curl_error = htmlentities($xml->error, ENT_QUOTES, 'UTF-8');
			$curl_error = xss_clean($curl_error);
			$this->EE->TMPL->log_item('Twitterfeed module : Twitter API error : ' . $curl_error);   
			return FALSE;
		}
            
		// or if Twitter returns anything in its children that has a <title> node, there was some kind of error because it's returning an HTML error page!
		else if ((count($nodes) > 0) && ($nodes[0]->title))
		{
			$twitter_error = htmlentities($nodes[0]->title, ENT_QUOTES, 'UTF-8');
			$twitter_error = xss_clean($twitter_error);
			$this->EE->TMPL->log_item('Twitterfeed module : Twitter API error : ' . $twitter_error);
			return FALSE;
		}
		else 
		{
			$rightnow = time();	        
			$this->_feed_data = $xml;
			$this->_feed_timestamp = $rightnow;
			$this->EE->TMPL->log_item('Twitterfeed module : cURL exec completed successfully, new feed retrieved');     
			return TRUE;
		}
    }	
}

