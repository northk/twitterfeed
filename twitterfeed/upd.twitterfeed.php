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

 
class Twitterfeed_upd {

/** --------------------------------------------------
 *   Constructor
 *   --------------------------------------------------*/
    function __construct()
    {
        $this->EE =& get_instance();
    }

    
/** --------------------------------------------------
 *   Install : install the module, including building a database table to store the feed cache
 *   --------------------------------------------------*/
    function install() 
    {
		// Check for presense of cURL library; if not present log an error and quit.	
		if (function_exists("curl_version") == FALSE)
		{
			$this->EE->TMPL->log_item('Twitterfeed module : cURL library is required but not installed');
			return FALSE;
		}
		
		// Check if simplexml extension is installed
		if (extension_loaded('simplexml') == FALSE)
		{
			$this->EE->TMPL->log_item('Twitterfeed module : simplexml extension is required but not installed');
			return FALSE;
		}
		
		// Check if pcre library is installed
		if (extension_loaded('pcre') == FALSE)
		{
			$this->EE->TMPL->log_item('Twitterfeed module : pcre library is required but not installed');
			return FALSE;
		}
		
        $this->EE->load->dbforge();
        
		//  Add Twitterfeed to the modules table so EE knows about it

        $this->EE->db->insert('modules', array(
            'module_name' => 'Twitterfeed' ,
            'module_version' => Twitterfeed_config::TWITTERFEED_VERSION,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n'
        ));
        
		//  Drop (if it exists) and re-create the Twitterfeed cache table
		//
        // Installing will always rebuild the table, so you could uninstall and then reinstall the module 
		// in order to force clearing of the cache in the database.
		//

		$this->EE->dbforge->drop_table(Twitterfeed_config::TWITTERFEED_TABLE);
		
        $this->EE->dbforge->add_field(array(
            'screen_name' => array('type' => 'varchar', 'constraint' => Twitterfeed_config::TWITTERFEED_MAX_SCREEN_NAME_LENGTH, 'null' => FALSE),
            'feed_data' => array('type' => 'varchar', 'constraint' => Twitterfeed_config::TWITTERFEED_MAX_FEED_SIZE, 'null' => FALSE),
			'feed_timestamp' => array('type' => 'bigint', 'null' => FALSE)
        ));

        $this->EE->dbforge->add_key('screen_name', TRUE);
        $this->EE->dbforge->create_table(Twitterfeed_config::TWITTERFEED_TABLE);
        return TRUE;
    }


/** --------------------------------------------------
 *   Update : check to see if this version of the module is newer than what is recorded in the db. 
 *   If so, take appropriate actions
 *   --------------------------------------------------*/
    function update($current = '')
    {
        if (version_compare($current, Twitterfeed_config::TWITTERFEED_VERSION, '='))
        {
            return FALSE;
        }
    
        if (version_compare($current, Twitterfeed_config::TWITTERFEED_VERSION, '<'))
        {
            // do any update code needed here
        }
    
        return TRUE;
    }    
    
    
/** --------------------------------------------------
 *   Unistall : remove the module record and and drop the Twitterfeed cache table
 *   --------------------------------------------------*/
    function uninstall()
    {
        $this->EE->load->dbforge();
        
        // remove any mention of the Twitterfeed module from EE module_member_groups table
    
        $this->EE->db->select('module_id');
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Twitterfeed'));
        
        $this->EE->db->where('module_id', $query->row('module_id'));
        $this->EE->db->delete('module_member_groups');        

		// remove row from EE modules table
	
	    $this->EE->db->delete('modules', array('module_name' => 'Twitterfeed'));

		// drop the cache db table
		
		$this->EE->dbforge->drop_table(Twitterfeed_config::TWITTERFEED_TABLE);

		return TRUE;
    }
}    
    