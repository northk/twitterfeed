<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

class Twitterfeed_config
{
    const TWITTERFEED_VERSION = "1.0";
    const TWITTERFEED_TABLE = "twitterfeed_cache";
	const TWITTERFEED_CURL_TIMEOUT = 1; // how long (seconds) to give the HTTP GET request from Twitter, before we timeout.
	
	const  TWITTERFEED_MAX_SCREEN_NAME_LENGTH = 15; // used for creating column in db table
	
	// Why 60K for maximum cache data size of an individual Twitter feed? 
	// Looking at a sample XML result from Twitter, it appears that each tweet is about 3K of data.
	// 3K * 20 tweets maximum for a given feed = 60K. This is used for the varchar column size in the db.
	const TWITTERFEED_MAX_FEED_SIZE = 60000;
	
	const TWITTERFEED_DEFAULT_NUM_MESSAGES_TO_RETRIEVE = 5;
	const TWITTERFEED_MAX_MESSAGES_TO_RETRIEVE = 20;
	
	// Time (in minutes) of how old the cache should be before requesting new data from Twitter.	
	const TWITTERFEED_DEFAULT_CACHE_TIME_TO_STALE = 45; 
}

