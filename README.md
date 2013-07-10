Twitterfeed 1.0 Beta addon for ExpressionEngine
===============================


**NOTE: Twitter has deprecated their xml rest API since this addon was written. As a result, this addon will no longer work. It would need to be modified to use the new Twitter 1.1 JSON API. See https://dev.twitter.com/docs/api/1.1/overview for details.**

**There are [several addons available](http://devot-ee.com/search/results?keywords=twitter&addon_version_support=ee2) now for providing a Twitter feed for EE so you have lots of options. Best wishes!**

Twitterfeed is a module for the [ExpressionEngine](http://expressionengine.com/) content management system. It grabs, caches and displays a
public user timeline from Twitter (yours or someone else's, as long is it's a public feed). There are some other great addons which provide similar
functionality at [devot-ee.com](http://devot-ee.com/), including the official [Twitter
Timeline](http://expressionengine.com/downloads/details/twitter_timeline/) addon by Ellis Labs.

However, I wrote my own addon because there are a couple of things I wanted to do differently:  

1. Other addons cache the Twitter feed data to a file on the file system, and I wanted to use the database to handle caching. I believe this
approach will scale better for high traffic sites. The database is designed to handle and manage high numbers of concurrent users and I don't
have to look at stuff like `@flock($fp, LOCK_SH | LOCK_NB)` in my code.

2. I wanted the Twitter feed to reliably display data even when the Twitter [rate limit](https://dev.twitter.com/docs/rate-limiting) has been
exceeded for your IP address, or there was some other Twitter API error. Twitter only allows 150 API calls per hour from one IP address. If you
are on a shared host, that limit will quickly get exceeded if there are other web sites calling Twitter's API. When that happens you get an error
back from Twitter-- but no data. This module will almost always display your feed, even if the cache is old.

3. In the future I intend to add [OAuth authentication](https://dev.twitter.com/docs/auth/oauth) to the addon. This will allow you to display a
timeline from your personal Twitter account via an authenticated feed. This approach will bump up your rate limit from 150 API calls per hour, to 350 calls. Plus, those 350 calls are counted against your OAuth token rather than the IP address you are hosted on. What
this means in practical terms is you'll be able to get an updated feed from your timeline a lot more reliably, rather than having to display old data
from your cached feed so often. However the current version doesn't use OAuth yet.


### See it in action
**This code is a Beta version and I don't recommend using it on critical customer websites**. However I've done a lot of manual testing, I'm
using it on [my site](http://www.highintegritydesign.com), and it's working great! Take a look at the code and you'll see I'm doing a lot to keep
the output secure and to handle edge cases gracefully (and if you have any suggestions, let me know).


### Installation
For the addon to work, you'll need PHP 5.1 or newer with the cURL library, pcre library, and simplexml extension installed on your web host. When you
install the module, it will check to see if these libraries and extensions are installed. If you install the module and nothing shows up when you use
the tags in your templates, please try the trouble-shooting section below.

Installation is pretty straightforward:  

1. Upload the twitterfeed folder and subfolders from Github, to your system/expressionengine/third_party folder.

2. Install the module in ExpressionEngine by going to Add-Ons->Modules.

3. Use the tags and parameters in your templates to get your Twitter feed.  

4. Tweet how much you like my addon!  


### Parameters and variables
You can use the following parameters in your EE tag:

*    `screen_name="ellislab"` - The twitter name of the timeline to show. Required.
	
*    `limit="5"` - Number of status messages to retrieve.  Default is 5. Maximum value is 20 (this value is configurable in the addon's config.php).
		  
*    `cache_refresh="45"` - Time (in minutes) of how old the cache should be before requesting new data from Twitter.  Defaults to 45 (this value is configurable in the addon's config.php).
		
*    `include_rts="yes"` - When set to yes, the timeline will contain native retweets (if they exist) in addition to the standard stream of tweets. 

The following variables are available inside of the tag. They will return the data from each Tweet:		

        {count}
        {created_at format="%m-%d-%Y"}
        {screen_name}
        {name}
        {text}
        {location}
        {description}
        {profile_image_url}
        {url}


Here is an example:    
  
  
        {exp:twitterfeed screen_name="northk" limit="10"}  
            <div>  
                <h3>{created_at format="%F %j, %Y"} by {screen_name}</h3>  
                <img src="{profile_image_url}" width="50" height="50" alt="profile image" />  
                <p>{text}</p>  
                <p>by {name} from {location}</p>  
                <p>{description}</p>  
                <p>{url}</p>  
                <p>{count}</p>  
            </div>  
        {/exp:twitterfeed}



### Trouble-shooting
The module will log informational messages plus any errors to the EE template parsing log. To see the log, go to System Administration->Output
and Debugging and turn on Display Template Debugging. Then, if you are logged into the EE control panel you will see the full template parsing
log as you browse your site. Any errors will be shown here.

Note that if there is no cached data for the Twitter user timeline you want, and Twitter's API call fails due to rate limiting or some other problem,
then there's nothing else the module can do. In that scenario it will return no_results (which you will see in the template parsing log), and the log
will also tell you the feed you requested is not cached nor could it get results from Twitter. Once the module gets some data from Twitter, it
will always use that data from the cache even if it's old. So, once you get good data from Twitter for a given user name (even once), you should have good feed data to display.


### Licensing
This code is licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php).

Written by North Krimsly of [www.highintegritydesign.com](http://www.highintegritydesign.com)
