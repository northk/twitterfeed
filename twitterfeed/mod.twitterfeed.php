<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
require_once(PATH_THIRD . 'twitterfeed/config.php');
require_once(PATH_THIRD . 'twitterfeed/twitterfeed_helper.php');

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


class Twitterfeed {

    public $return_data = NULL;
	
	private $_screen_name = NULL; // The twitter name of the timeline to show. Required.
	private $_limit = Twitterfeed_config::TWITTERFEED_DEFAULT_NUM_MESSAGES_TO_RETRIEVE; 
	private $_cache_refresh = Twitterfeed_config::TWITTERFEED_DEFAULT_CACHE_TIME_TO_STALE;
	private $_include_rts = 'yes'; // if 'yes', timeline will contain native retweets in addition to the standard stream of tweets. 
	
	private $_tweets = NULL;
	
	
	
/** --------------------------------------------------
 *   Constructor
 *   --------------------------------------------------*/
    function __construct()
    {
        // Make a local reference to the ExpressionEngine super object
        $this->EE =& get_instance();

		$this->_screen_name = $this->EE->TMPL->fetch_param('screen_name');
		$this->_limit = $this->EE->TMPL->fetch_param('limit', $this->_limit);
		$this->_cache_refresh = $this->EE->TMPL->fetch_param('cache_refresh', $this->_cache_refresh);
		$this->_include_rts = $this->EE->TMPL->fetch_param('include_rts', $this->_include_rts);
		
		// Check parameters for correctness
		if ( !($this->_screen_name)) 
		{
			$this->EE->TMPL->log_item('Twitterfeed module : screen_name parameter required but not supplied');
            $this->return_data = $this->EE->TMPL->no_results();        			
			return;
		}

		// validate Twitter user name. 
        $pattern = '/^[[:alnum:]_]{1,15}$/';
        if (preg_match($pattern, $this->_screen_name) == 0) 
		{
			$this->EE->TMPL->log_item('Twitterfeed module : screen_name parameter is not a valid Twitter screen name');
            $this->return_data = $this->EE->TMPL->no_results();        			
            return;
        }
			
		if ((is_numeric($this->_limit) == FALSE) || ($this->_limit < 0) || ($this->_limit > Twitterfeed_config::TWITTERFEED_MAX_MESSAGES_TO_RETRIEVE))
		{
			$this->EE->TMPL->log_item('Twitterfeed module : limit parameter out of range');
            $this->return_data = $this->EE->TMPL->no_results();        			
			return;				
		}

		if ((is_numeric($this->_cache_refresh) == FALSE) || ($this->_cache_refresh < 0)) 
		{
			$this->EE->TMPL->log_item('Twitterfeed module : cache_refresh parameter out of range');
            $this->return_data = $this->EE->TMPL->no_results();        			
			return;				
		}
		
		if (($this->_include_rts != 'yes') && ($this->_include_rts != 'no'))
		{
			$this->EE->TMPL->log_item('Twitterfeed module : include_rts parameter must be "yes" or "no"');	
            $this->return_data = $this->EE->TMPL->no_results();        			
			return;
		}
		
		// construct a new Twitterfeed_tweets object and attempt to read the tweets either from the db cache
		// or from the Twitter API. If both of those fail for some reason, return no_results.
        $this->_tweets = new Twitterfeed_tweets($this->_screen_name, $this->_limit, $this->_cache_refresh, $this->_include_rts);
        $this->return_data = $this->_tweets->get_tweets();
        if ($this->return_data === FALSE)
        {
            $this->return_data = $this->EE->TMPL->no_results();        
        }
	}	
}



/**   ------------------
        TROUBLESHOOTING:
        ------------------
		
		If any errors or problems are encountered as the add-on runs, they will be logged in the EE template parsing log.  
		You can see the template parsing log by enabling template debugging in the control panel, by going to
		System Administration->Output and Debugging.
*/		


/**	------------------
		PARAMETERS:
		------------------

		screen_name="ellislab"
		- The twitter name of the timeline to show. Required.
		
		limit="5"
		- Number of status messages to retrieve.  Default is 5. Maximum value is 20.
		  (this value is configurable in the addon's config.php)
		  
		cache_refresh="45"
		- Time (in minutes) of how old the cache should be before requesting new data from Twitter.  Defaults to 45. 
		  (this value is configurable in the addon's config.php)
		
		include_rts="no"
		- When set to yes, the timeline will contain native retweets (if they exist) in addition to the standard stream of tweets. 
		
		------------------
		VARIABLES:
		------------------
		
		{count}
		{created_at format="%m-%d-%Y"}
		{screen_name}
		{name}
		{text}
		{location}
		{description}
		{profile_image_url}
		{url}
*/		