<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

#input_is_safe();
#mysql_is_safe();

require_once('../include/init.php');

site_project_header(array('group'=>$group_id,
			  'context'=>'searchingroup'));

# Ideally, we would like to do a real site wide search, which mean being able 
# to do a search without selecting a given tracker.
# To do that, we must run a SQL command per tracker, put everything in an 
# array. Fine, but the current function returns SQL results, and we wont 
# change that right now. So we ask the user to provide input.
# But it has to be kept in mind that we want this page to allow a search
# over all trackers, in the end.
print '<p>'.sprintf(_("With the following form, you can perform a search in the items summaries and details of a given tracker of the project %s. If you need to perform more complex search, use the query forms in Browse items page of this tracker. If you want to perform a site-wide search, use the search box in the left menu."), group_getname($group_id)).'</p>';
# use the string alltrackers to say we want a search to be performed over 
# all of them
# (this must not be in a boxoption, it is no option, it is the only purpose of
# the page)
print '<p>'.search_box('', '', 45).'</p>';

site_project_footer(array());
