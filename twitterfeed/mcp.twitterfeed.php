<?php if ( !defined('BASEPATH')) exit('No direct script access allowed');
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

class Twitterfeed_mcp {

    function __construct()
    {
        $this->EE =& get_instance();
    }
}