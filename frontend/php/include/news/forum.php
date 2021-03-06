<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>.
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


function forum_show_a_nested_message ($result,$row=0)
{
	/*

	accepts a database result handle to display a single message
		in the format appropriate for the nested messages

		second param is which row in that result set to use

	*/
	global $sys_datefmt;

	$g_id =  db_result($result,$row,'group_id');

	# if the forum is a piece of news then get the real group_id from the
	# news_byte table
	if ($g_id == $GLOBALS['sys_group_id'])
        {
            $f_id =  db_result($result,$row,'group_forum_id');
            $gr = db_execute("SELECT group_id FROM news_bytes WHERE forum_id=?", array($f_id));
            $g_id = db_result($gr,0,'group_id');
        }

	$ret_val = '
		<table border="0" width="100%">
			<tr>
				<td class="boxitem">'.
		'<strong>'.db_result($result, $row, 'subject').'</strong>'.
		' ('._("posted by").' <a href="'.$GLOBALS['sys_home'].'users/'.
		db_result($result, $row, 'user_name') .'/">'.
		db_result($result, $row, 'realname') .'</a>, '.
		utils_format_date(db_result($result,$row,'date')).')'.
		'                           </td>
			</tr><tr>
				<td><p>
					'. markup_rich(db_result($result,$row,'body')) .
		'                                   </p><p><a href="'.$GLOBALS['sys_home'].'forum/message.php?msg_id='.db_result($result, $row, 'msg_id') .'#followup">[ '._("Reply").' ]</a>'.
		'                           </p></td>
			</tr>
		</table>';
	return $ret_val;
}

function forum_show_nested_messages ($thread_id, $msg_id) {
	global $total_rows,$sys_datefmt;

	$result = db_execute("SELECT user.user_name,forum.has_followups,user.realname,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id  ".
		"FROM forum,user,forum_group_list WHERE forum.thread_id = ? AND user.user_id=forum.posted_by AND forum.is_followup_to = ? AND forum_group_list.group_forum_id = forum.group_forum_id ".
		"ORDER BY forum.date ASC", array($thread_id, $msg_id));
	$rows=db_numrows($result);

	$ret_val='';

	if ($result && $rows > 0)
        {
            $ret_val .= '
			<UL>';

            /*

            iterate and show the messages in this result

			for each message, recurse to show any submessages

            */
            for ($i=0; $i<$rows; $i++)
                {
                    #	increment the global total count
                    $total_rows++;

                    #	show the actual nested message
                    $ret_val .= forum_show_a_nested_message ($result,$i).'<P>';
                    if (db_result($result,$i,'has_followups') > 0)
                        {
                            #	Call yourself if there are followups
                            $ret_val .= forum_show_nested_messages ( $thread_id, db_result($result,$i,'msg_id') );
                        }
                }
            $ret_val .= '
			</UL>';
        }

	return $ret_val;
}

# FIXME: site_project_header function should be used instead
function forum_header($params)
{
	global $DOCUMENT_ROOT,$HTML,$group_id,$forum_name,$thread_id,$msg_id,$forum_id,$REQUEST_URI,$sys_datefmt,$et,$et_cookie;

	$params['group']=$group_id;
	$params['toptab']='forum';

	# NEWS ADMIN
	#this is a news item for the whole system or a for a project,
	# not a regular forum: forum are deactivated in savannah
	if ($forum_id)
        {
            # Show this news item at the top of the page
            $result=db_execute("SELECT * FROM news_bytes WHERE forum_id=?", array($forum_id));

            # if the result is empty, this is not a news item, but a forum.
            if (!$result || db_numrows($result) < 1)
                {
                    $is_news=0;
                    #print '<span class="error">'._("Error").' - '._("This news item was not found").'</span>';
                    #print "forum_name=$forum_name<br />";
                    site_project_header($params);
                    #site_project_header(array('group'=>$group_id,'context'=>'forum'));

                }
            else
                {
                    $is_news=1;
                    #backwards shim for all "generic news" that used to be submitted
                    #as of may, "generic news" is not permitted - only project-specific news

		    # FIXME: group_name is not set, it brokes the top menu.

                    $params['group']=db_result($result,0,'group_id');

		    $group_id = db_result($result,0,'group_id');
                    $params['toptab']='news';
                    site_project_header($params);

		    print '
<div class="indexright">
';
                    print $HTML->box_top(_("Latest News"));
                    print news_show_latest(db_result($result,0,'group_id'),5, "false");
                    print $HTML->box_bottom();
		    print '</div>
<div class="indexcenter">
';

		    print "<h3><a href='forum.php?forum_id=$forum_id'>".db_result($result,0,'summary')."</a></h3>";
                    print '<p><em>'.sprintf(_("Item posted by %s on %s."),
                        utils_user_link(user_getname(db_result($result,0,'submitted_by')),
                        user_getrealname(db_result($result,0,'submitted_by'))),
                        utils_format_date(db_result($result,0,'date')))
                        .'</em></p>';
                    print markup_full(db_result($result,0,'details'));

# could this fix the bug #409 ?
                    $forum_name = db_result($result,0,'summary');
		    print '</div>
';

                }
        }


	/*
		Show horizontal forum links
	*/
	if ($forum_id && $forum_name && $is_news)
        {
            print '<h3 class="clearr">'.html_anchor(_("Comments:"), "comments").'</h3>';
        }
	print '<P><strong>';

	# yeupou, 2006-09-22: deactivate for now, it does not seem to be
	# working and generate garbage (like mail sent to NOREPLY)
	if (0 && $forum_id && user_isloggedin())
        {
            print '<a href="'.$GLOBALS['sys_home'].'forum/monitor.php?forum_id='.$forum_id.'">' .
                #'<img src="'.$GLOBALS['sys_home'].'images/ic/check.png" alt="monitor" />
                _("Monitor Forum (toggle)").'</a> | '.
                '<a href="'.$GLOBALS['sys_home'].'forum/save.php?forum_id='.$forum_id.'">'.
                #'<img src="'.$GLOBALS['sys_home'].'images/ic/save.png" alt="save" />
                _("Save Place").'</a> | '.
                '<A HREF="'.$GLOBALS['sys_home'].'forum/who_monitors.php?forum_id='.$forum_id.'?group_id='.$group_id.'">'.
                'Who\'s monitoring</A> | '.
                '<A HREF="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='.$forum_id.'">'.
                'View Forum</A>';
        }

	/*  This functionality is depreated
    if (user_is_super_user()) alternatively: user_ismember($group_id, "F2")
      {
        print ' | <A HREF="'.$GLOBALS['sys_home'].'forum/admin/?group_id='.$group_id.'">Admin</A></strong>';
      }
	*/
	print '</strong></P>';
}

# Backward compatibility
function forum_footer($params){
	site_project_footer($params);
}

function forum_create_forum($group_id,$forum_name,$is_public=1,$create_default_message=1,$description='')
{
	global $feedback;
	/*
		Adding forums to this group
	*/
	$fields = array();
	$fields['group_id'] = $group_id;
	$fields['forum_name'] = htmlspecialchars($forum_name);
	$fields['is_public'] = $is_public;
	$fields['description'] = htmlspecialchars($description);

	$result = db_autoexecute('forum_group_list',
	  array('group_id' => $group_id,
		'forum_name' => htmlspecialchars($forum_name),
		'is_public' => $is_public,
		'description' => htmlspecialchars($description)),
	  DB_AUTOQUERY_INSERT);
	
	if (!$result)
        {
            " Error Adding Forum ";
        }
	else
        {
            " Forum Added ";
        }
	$forum_id = db_insertid($result);

	if ($create_default_message)
        {
            #set up a cheap default message
            $result2 = db_autoexecute('forum',
              array('group_forum_id' => $forum_id,
		    'posted_by' => 100,
		    'subject' => 'Welcome to $forum_name',
		    'body' => 'Welcome to $forum_name',
		    'date' => time(),
		    'is_followup_to' => 0,
		    'thread_id' => get_next_thread_id()),
	      DB_AUTOQUERY_INSERT);
        }
	return $forum_id;
}

function get_forum_name($id){
	/*
		Takes an ID and returns the corresponding forum name
	*/
        $result = db_execute("SELECT forum_name FROM forum_group_list WHERE group_forum_id=?", array($id));
	if (!$result || db_numrows($result) < 1)
        {
            return "Not Found";
        }
	else
        {
            return db_result($result, 0, "forum_name");
        }

}

function show_thread($thread_id,$et=0)
{
	/*
		Takes a thread_id and fetches it, then invokes show_submessages to nest the threads

		$et is whether or not the forum is "expanded" or in flat mode
	*/
	global $total_rows,$sys_datefmt,$is_followup_to,$subject,$forum_id,$current_message;

	$result = db_execute("SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id=? AND user.user_id=forum.posted_by AND forum.is_followup_to=0 ".
		"ORDER BY forum.msg_id DESC", array($thread_id));

	$total_rows=0;
	$ret_val = '';
	if (!$result || db_numrows($result) < 1)
        {
            return 'Broken Thread';
        }
	else
        {

            $title_arr=array();
            $title_arr[]='Thread';
            $title_arr[]='Author';
            $title_arr[]='Date';

            $ret_val .= html_build_list_table_top ($title_arr);

            $rows=db_numrows($result);
            $is_followup_to=db_result($result, ($rows-1), 'msg_id');
            $subject=db_result($result, ($rows-1), 'subject');
            /*
	Short - term compatibility fix. Leaving the iteration in for now -
	will remove in the future. If we remove now, some messages will become hidden

	No longer iterating here. There should only be one root message per thread now.
	Messages posted at the thread level are shown as followups to the first message
			*/
			for ($i=0; $i<$rows; $i++)
                {
                    $total_rows++;
                    $ret_val .= '<TR class="'. utils_get_alt_row_color($total_rows) .'"><TD>'.
                        (($current_message != db_result($result, $i, 'msg_id'))?'<A HREF="'.$GLOBALS['sys_home'].'forum/message.php?msg_id='.db_result($result, $i, 'msg_id').'">':'').
                        '<IMG SRC="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/contexts/mail.png" BORDER=0 HEIGHT=12 WIDTH=12 /> ';
                    /*
				See if this message is new or not
                    */
                    if (get_forum_saved_date($forum_id) < db_result($result,$i,'date'))
                        { $ret_val .= '<strong>'; }

                    $ret_val .= db_result($result, $i, 'subject') .'</A></TD>'.
                        '<TD>'.db_result($result, $i, 'user_name').'</TD>'.
                        '<TD>'.utils_format_date(db_result($result,$i,'date')).'</TD></TR>';
                    /*
				Show the body/message if requested
                    */
                    if ($et == 1)
                        {
                            $ret_val .= '
				<TR class="'. utils_get_alt_row_color($total_rows) .'"><TD>&nbsp;</TD><TD COLSPAN=2>'.
                                nl2br(db_result($result, $i, 'body')).'</TD><TR>';
                        }

                    if (db_result($result,$i,'has_followups') > 0)
                        {
                            $ret_val .= show_submessages($thread_id,db_result($result, $i, 'msg_id'),1,$et);
                        }
                }
			$ret_val .= '</TABLE>';
        }
	return $ret_val;
}

function show_submessages($thread_id, $msg_id, $level,$et=0)
{
	/*
		Recursive. Selects this message's id in this thread,
		then checks if any messages are nested underneath it.
		If there are, it calls itself, incrementing $level
		$level is used for indentation of the threads.
	*/
	global $total_rows,$sys_datefmt,$forum_id,$current_message;

	$result = db_execute("SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id = ? ".
		"AND user.user_id=forum.posted_by AND forum.is_followup_to = ? ".
		"ORDER BY forum.msg_id ASC", array($thread_id, $msg_id));
	$rows=db_numrows($result);

	$ret_val = '';
	if ($result && $rows > 0) {
        /*   US changed treatment of background color. Messages belonging to the same thread get the same background color as the first
               message of that threat
        */
		for ($i=0; $i<$rows; $i++) {
			/*
				Is this row's background shaded or not?
			        $total_rows++;
			*/
            $total_rows++;

            $ret_val .= '<TR class="'. utils_get_alt_row_color($total_rows) .'"><TD NOWRAP>';
			/*
				How far should it indent?
			*/

			for ($i2=0; $i2<$level; $i2++)
                {
                    $ret_val .= ' &nbsp; &nbsp; &nbsp; ';
                }


            $ret_val .= '<IMG SRC="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/contexts/mail.png" BORDER=0 HEIGHT=12 WIDTH=12 /> ';

			/*
				If it this is the message being displayed, don't show a link to it
			*/

            $ret_val .= (($current_message != db_result($result, $i, 'msg_id'))?
                         '<A HREF="'.$GLOBALS['sys_home'].'forum/message.php?msg_id='.db_result($result, $i, 'msg_id').'">':'');
			/*
				See if this message is new or not
			*/
			if (get_forum_saved_date($forum_id) < db_result($result,$i,'date'))
                { $ret_val .= '<strong>'; }

			$ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
				'<TD>'.db_result($result, $i, 'user_name').'</TD>'.
				'<TD>'.utils_format_date(db_result($result,$i,'date')).'</TD></TR>';

			/*
				Show the body/message if requested
			*/
			if ($et == 1)
                {
                    $ret_val .= '
					<TR class="'. utils_get_alt_row_color($total_rows) .'"><TD>&nbsp;</TD><TD COLSPAN=2>'.
                        nl2br(db_result($result, $i, 'body')).'</TD><TR>';
                }

			if (db_result($result,$i,'has_followups') > 0)
                {
                    /*
					Call yourself, incrementing the level
                    */
                    $ret_val .= show_submessages($thread_id,db_result($result, $i, 'msg_id'),($level+1),$et);
                }
		}
	}
	return $ret_val;
}

function get_next_thread_id()
{
	/*
		Get around limitation in MySQL - Must use a separate table with an auto-increment
	*/
	$result=db_query("INSERT INTO forum_thread_id VALUES ('')");

	if (!$result)
        {
            print '<H1>Error!</H1>';
            print db_error();
            exit;
        }
	else
        {
            return db_insertid($result);
        }
}

function get_forum_saved_date($forum_id)
{
	/*
		return the save_date for this user
	*/
	global $forum_saved_date;


	if ($forum_saved_date)
        {
            return $forum_saved_date;
        }
	else
        {
	    $result = db_execute("SELECT save_date FROM forum_saved_place WHERE user_id=? AND forum_id=?",
				 array(user_getid(), $forum_id));
            if ($result && db_numrows($result) > 0)
                {
                    $forum_saved_date=db_result($result,0,'save_date');
                    return $forum_saved_date;
                }
            else
                {
                    #highlight new messages from the past week only
                    $forum_saved_date=(time()-604800);
                    return $forum_saved_date;
                }
        }
}

function post_message($thread_id, $is_followup_to, $subject, $body, $group_forum_id)
{
	global $feedback;
	if (user_isloggedin())
        {
            if (!$group_forum_id)
                {
                    exit_error('Error',_("Trying to post without a forum ID"));
                }
            if (!$body || !$subject)
                {
                    exit_error('Error',_("Must include a message body and subject"));
                }

            #see if that message has been posted already for all the idiots that double-post
            $res3=db_execute("SELECT * FROM forum ".
			     "WHERE is_followup_to=? ".
			     "AND subject=? ".
			     "AND group_forum_id=? ".
			     "AND posted_by=?",
			     array($is_followup_to, htmlspecialchars($subject),
				   $group_forum_id, user_getid()));

            if (db_numrows($res3) > 0)
                {
                    #already posted this message
                    exit_error('Error',_("You appear to be double-posting this message, since it has the same subject and followup information as a prior post."));
                }
            else
                {
                    print db_error();
                }

            if (!$thread_id)
                {
                    $thread_id=get_next_thread_id();
                    $is_followup_to=0;
                }
            else
                {
                    if ($is_followup_to)
                        {
                            #increment the parent's followup count if necessary
                            $res2=db_execute("SELECT * FROM forum WHERE msg_id=? AND thread_id=? AND group_forum_id=?",
                                             array($is_followup_to, $thread_id, $group_forum_id));
                            if (db_numrows($res2) > 0)
                                {
                                    if (db_result($result,0,'has_followups') > 0)
                                        {
                                            #parent already is marked with followups
                                        }
                                    else
                                        {
                                            #mark the parent with followups as an optimization later
                                            db_execute("UPDATE forum SET has_followups='1' WHERE msg_id=?
                                                        AND thread_id=? AND group_forum_id=?",
                                                       array($is_followup_to, $thread_id, $group_forum_id));
                                        }
                                }
                            else
                                {
                                    exit_error('Error',_("Trying to followup to a message that doesn't exist."));
                                }
                        }
                    else
                        {
                            #should never happen except with shoddy browsers or mucking with the HTML form
                            exit_error('Error',_("No followup ID present when trying to post to an existing thread."));
                        }
                }

            $result = db_autoexecute('forum',
              array(
                'group_forum_id' => $group_forum_id,
                'posted_by' => user_getid(),
                'subject' => htmlspecialchars($subject),
                'body' => htmlspecialchars($body),
                'date' => time(),
                'is_followup_to' => $is_followup_to,
                'thread_id' => $thread_id
              ), DB_AUTOQUERY_INSERT);

            if (!$result)
                {
                    print "INSERT FAILED";
                    print db_error();
                    ' '._("Posting Failed").' ';
                }
            else
                {
                    ' '._("Message Posted").' ';
                }

            $msg_id=db_insertid($result);
            handle_monitoring($group_forum_id,$msg_id);

        }
	else
        {

            print '<h3>'._("You could post if you were logged in").'.</h3>';

        }

}

function show_post_form($forum_id, $thread_id=0, $is_followup_to=0, $subject="")
{

	if (user_isloggedin())
        {
            if ($subject)
                {
                    #if this is a followup, put a RE: before it if needed
                    if (!eregi('RE:',$subject,$test))
                        {
                            $subject ='RE: '.$subject;
                        }
                }

            print '<center>';
            print '<form action="'.$GLOBALS['sys_home'].'forum/forum.php" method="POST">';
            print '<input type="hidden" name="post_message" value="y" />';
            print '<input type="HIDDEN" name="forum_id" value="'.$forum_id.'" />';
            print '<input type="HIDDEN" name="thread_id" value="'.$thread_id.'" />';
            print '<input type="HIDDEN" name="is_followup_to" value="'.$is_followup_to.'" />';
            print '<table><tr><td><strong>'._("Subject").':</td><td>';
            print '<input type="TEXT" name="subject" value="'.$subject.'" size="60" maxlength="45" />';
            print '</td></tr>';
            print '<tr><td><strong>'._("Message:").'</td><td>';
            print '<textarea name="body" value="" rows="25" cols="60" wrap="SOFT"></textarea>';
			print '</td></tr>';
			print '<tr><td colspan="2" align="MIDDLE">';
			print '<span class="warn">HTML tags will display in your post as text</span>';
			print '<br />';
			print '<input type="SUBMIT" name="SUBMIT" value="'._("Post Comment").'" />';
			print '</td></tr></table>';
			print '</form>';
			print '</center>';

		}
	else
        {
            print "<center>";
            print "\n\n<span class=\"error\">"._("You could post if you were logged in").'</span>';
            print "</center>";

        }

}

function handle_monitoring($forum_id,$msg_id)
{
	global $feedback;
	/*
		Checks to see if anyone is monitoring this forum
		If someone is, it sends them the message in email format
	*/

	$result=db_execute("SELECT user.email from forum_monitored_forums,user ".
			   "WHERE forum_monitored_forums.user_id=user.user_id AND forum_monitored_forums.forum_id=?",
			   array($forum_id));
	$rows=db_numrows($result);

	if ($result && $rows > 0)
        {
            $tolist=implode(result_column_to_array($result),', ');

	    $result = db_execute("SELECT groups.unix_group_name,user.user_name,forum_group_list.forum_name,user.email,user.realname,".
                "forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ".
                "FROM forum,user,forum_group_list,groups ".
                "WHERE user.user_id=forum.posted_by ".
                "AND forum_group_list.group_forum_id=forum.group_forum_id ".
                "AND groups.group_id=forum_group_list.group_id ".
                "AND forum.msg_id=?", array($msg_id));

            if ($result && db_numrows($result) > 0)
                {
                    if ($GLOBALS['sys_lists_enable'] == "yes")
                        {
                            /* NOTE: This configuration variable (sys_lists_enable) can be turned off
                             * (commented out) in the main savannah configuration file. Turning it on
                             * will make mails appear from the user that posted the forum entry
                             * instead of the usual savannah system (sys_mail_replyto). Additionally
                             * a reply-to will be set to
                             *      <prefix><project>_<forum>@<webserver_host>
                             *
                             * This is made in order to use a self written mailinglist which is integrated
                             * Into the savannah forum. See sv_forums for details
                             */
                            /* AH 2002-11-14 start */
                            $decid = sprintf("%07d",$msg_id);
                            $checksum = md5($decid);
                            $gpkaid = $decid . substr($checksum,0,1) . substr($checksum,2,1) .
                                substr($checksum,26,1) . substr($checksum,28,1) . substr($checksum,30,1);

/* MH 2003-11-20 */
                            $from=db_result($result,0,'realname')." <" . db_result($result,0, 'email') . ">";
                            /*$to=$GLOBALS[sys_mail_replyto];*/ /* MH: To should be the mailinglist address! */
                            $to=$GLOBALS['sys_lists_prefix'] . db_result($result,0,'unix_group_name').
                                "_" . db_result($result,0,'forum_name') . "@" . $GLOBALS['sys_default_domain'];

/*                            $subject= "[" . db_result($result,0,'unix_group_name').
                                "_" . db_result($result,0,'forum_name')."] ".
                                utils_unconvert_htmlspecialchars(db_result($result,0,'subject')).
                                "     #" . $gpkaid . "#";
*/
/* MH 2004-06-04 */
                             $subject= "[" . $GLOBALS['sys_lists_prefix'] . db_result($result,0,'unix_group_name').
                                " - " . db_result($result,0,'forum_name')."] ".
                                utils_unconvert_htmlspecialchars(db_result($result,0,'subject')).
                                "     #" . $gpkaid . "#";
                            $message="\n\n".
                                "\nBy: " . db_result($result,0,'realname') ." <" .db_result($result,0, 'email') . ">" .
                                "\n\n" . utils_unconvert_htmlspecialchars(db_result($result,0, 'body')).
                                "\n\n_______________________________________________".
                                "\nRead and respond to this message at: ".
                                "\nhttp://$GLOBALS[sys_default_domain]/forum/message.php?msg_id=".$msg_id.
                                "\nDo not alter the subject when replying! ".
                                "\nTo stop monitoring this forum, login to Savannah and visit: ".
                                "\nhttp://$GLOBALS[sys_default_domain]/forum/monitor.php?forum_id=$forum_id";
                            $savannah_project=db_result($result,0,'unix_group_name');
                            #$savannah_artifact="Forum";
                            #$savannah_artifact_id=$forum_id;
                            $savannah_artifact=0; # these must stay zero to not mess up the subject any further
                            $savannah_artifact_id=0;
                            /*$reply_to=$GLOBALS['sys_lists_prefix'] . db_result($result,0,'unix_group_name').
                                "_" . db_result($result,0,'forum_name') . "@" . $GLOBALS['sys_default_domain'];*/

                            /* AH 04/08/2005:
                               BCC should not be used to address reciepients. 
                               We use the Resent-To: header to address each person individually.
                               Thus we need to loop over recipients which are stored in the $tolist string.
                               (Could lead to performance problems with many recipients...) */

			    $toarray = explode(", ",$tolist);
			    for($xx=0;$xx<count($toarray);$xx++) {   /* LOOP OVER RECIPIENTS */
                               $additional_headers="Resent-To:" . $toarray[$xx];
			       $additional_headers .= "\nPrecedence: bulk\nResent-From: MailingForum";

# Custom extensions to the header:
/*
                            print "Just executed <br />".
                            "sendmail_mail (from: $from, <br />to: $to, <br />subject: $subject, <br />message: $message, <br />".
                                           "savannah_project: $savannah_project, <br />savannah_artifact: $savannah_artifact,<br />".
                                           "savannah_artifact_id: $savannah_artifact_id, <br />reply_to: $reply_to,<br />".
                                           "additional_headers: $additional_headers<br />";
*/

				sendmail_mail ($from, $to, $subject, $message,
                                           $savannah_project, $savannah_artifact,
                                           $savannah_artifact_id, $reply_to,
                                           $additional_headers);
			    }

/* AH 2002-11-14 stop */
                        }
                    else
                        {
/* MH 2003-11-20: executing sendmail is deprecated */
                            $from=$GLOBALS[sys_mail_replyto];
                            $to=$GLOBALS[sys_mail_replyto];
                            $subject="[" .db_result($result,0,'unix_group_name').
                                " - " . db_result($result,0,'forum_name')."] ".
                                utils_unconvert_htmlspecialchars(db_result($result,0,'subject'));
                            $message="\n\nRead and respond to this message at: ".
                                "\nhttp://$GLOBALS[sys_default_domain]/forum/message.php?msg_id=".$msg_id.
                                "\nBy: " . db_result($result,0, 'user_name') .
                                "\n\n" . utils_unconvert_htmlspecialchars(db_result($result,0, 'body')).
                                "\n\n______________________________________________________________________".
                                "\nYou are receiving this email because you elected to monitor this forum.".
                                "\nTo stop monitoring this forum, login to Savannah and visit: ".
                                "\nhttp://$GLOBALS[sys_default_domain]/forum/monitor.php?forum_id=$forum_id";
                            $savannah_project=db_result($result,0,'unix_group_name');
                            $savannah_artifact="Forum";
                            $savannah_artifact_id=$forum_id;
                            $reply_to=$GLOBALS[sys_mail_replyto];
                            $additional_headers="BCC: $tolist";

                            sendmail_mail ($from, $to, $subject, $message,
                                           $savannah_project, $savannah_artifact,
                                           $savannah_artifact_id, $reply_to,
                                           $additional_headers);
/* MH 2003-11-14 stop */
                        }

                    ' email sent - people monitoring ';
                }
            else
                {
                    ' email not sent - people monitoring ';
                    print db_error();
                }
        }
	else
        {
            ' email not sent - no one monitoring ';
            print db_error();
        }
}

function recursive_delete($msg_id,$forum_id)
{
	/*
		Take a message id and recurse, deleting all followups
	*/

	if ($msg_id=='' || $msg_id=='0' || (strlen($msg_id) < 1))
        {
            return 0;
        }

	$result=db_execute("SELECT msg_id FROM forum WHERE is_followup_to=? AND group_forum_id=?",
			   array($msg_id, $forum_id));
	$rows=db_numrows($result);
	$count=1;

	for ($i=0;$i<$rows;$i++)
        {
            $count += recursive_delete(db_result($result,$i,'msg_id'),$forum_id);
        }
	$toss = db_execute("DELETE FROM forum WHERE msg_id=? AND group_forum_id=?",
			   array($msg_id, $forum_id));

	return $count;
}

# US validate forum
function validate_forum_name ($forum_name) {
    return (ereg('^[a-zA-Z0-9\-]+$',$forum_name));
}
