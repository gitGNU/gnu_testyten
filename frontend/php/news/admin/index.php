<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once('../../include/init.php');
require_once('../../include/sane.php');


extract(sane_import('post', array('update', 'form_news_address')));

if ($group_id && user_ismember($group_id,'A'))
{
  $grp=project_get_object($group_id);

  if (!$grp->Uses("news"))
    { exit_error(_("Error"),_("This Project Has Turned Off News Tracker")); }
  
  if ($update) 
    { 
      db_execute("UPDATE groups SET new_news_address=? WHERE group_id=?",
		 array($form_news_address, $group_id));
      fb("Updated");
    }
  
  site_project_header(array('group'=>$group_id,'context'=>'anews'));


  print '<p>'._("You can view/change all of this tracker configuration from here.").'</p>';
  
  $res_grp = db_execute("SELECT new_news_address FROM groups WHERE group_id=?", array($group_id));
  $row_grp = db_fetch_array($res_grp);
  
  
  print '<h3>'._("News Tracker Email Notification Settings").'</h3>';
  print '
<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="group_id" value="'.$group_id.'" />';
  print '<span class="preinput">'._("Carbon-Copy List:").'</span><br />&nbsp;&nbsp;<INPUT TYPE="TEXT" NAME="form_news_address" VALUE="'.$row_grp['new_news_address'].'" SIZE="40" MAXLENGTH="255" />';
  print '
<p align="center"><input type="submit" name="update" value="'._("Update").'" />
</form>
';

  site_project_footer(array());
}
else
{
  exit_permission_denied();
}
