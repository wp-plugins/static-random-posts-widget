/*Static Random Posts Script
--Created by Ronald Huereca (ron@ronalfy.com)
--Created on: 10/25/2009
--Last modified on: 10/25/2009
--Relies on jQuery, wp-ajax-response

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
jQuery(document).ready(function() {
var $j = jQuery;
$j.staticrandomposts = {
	init: function() { initialize_links(); }
};
	//Initializes the refresh links
function initialize_links() {
	$j(".static-refresh").bind("click", function() { 
		//prepare object for AJAX call
		var obj = $j(this);
		obj.html(staticrandomposts.SRP_Loading);
		var s = {};
		s.response = 'ajax-response';
		var url = wpAjax.unserialize(obj.attr('href'));
		s.type = "POST";
		s.data = $j.extend(s.data, {action: url.action, number: url.number, name: url.name, _ajax_nonce: url._wpnonce});
		s.global = false;
		s.url = staticrandomposts.SRP_AjaxUrl;
		s.timeout = 30000;
		s.success = function(r) {
			obj.hide();
			obj.html(staticrandomposts.SRP_Refresh);
			//Parse the XML response
			var res = wpAjax.parseAjaxResponse(r, s.response);
			$j.each( res.responses, function() {
				if (this.what == "posts") {
					var data = this.data;
					$j("#static-random-posts-" + url.number).hide("slow", function() { 
						$j("#static-random-posts-" + url.number).html(data);
						$j("#static-random-posts-" + url.number).show("slow", function() { obj.show(); });
						return;
					});
				}
			});
		}
		$j.ajax(s);
		return false; 
	});
}
	$j.staticrandomposts.init();
});