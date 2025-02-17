<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (!permission_exists('call_block_view')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted data
	if (is_array($_POST['call_blocks'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_blocks = $_POST['call_blocks'];
	}

//copy the call blocks
	if (permission_exists('call_block_add')) {
		if ($action == 'copy' && is_array($call_blocks) && @sizeof($call_blocks) != 0) {
			//copy
				$obj = new call_block;
				$obj->copy($call_blocks);
			//redirect
				header('Location: call_block.php'.($search != '' ? '?search='.urlencode($search) : null));
				exit;
		}
	}

//toggle the call blocks
	if (permission_exists('call_block_edit')) {
		if ($action == 'toggle' && is_array($call_blocks) && @sizeof($call_blocks) != 0) {
			//toggle
				$obj = new call_block;
				$obj->toggle($call_blocks);
			//redirect
				header('Location: call_block.php'.($search != '' ? '?search='.urlencode($search) : null));
				exit;
		}
	}

//delete the call blocks
	if (permission_exists('call_block_delete')) {
		if ($action == 'delete' && is_array($call_blocks) && @sizeof($call_blocks) != 0) {
			//delete
				$obj = new call_block;
				$obj->delete($call_blocks);
			//redirect
				header('Location: call_block.php'.($search != '' ? '?search='.urlencode($search) : null));
				exit;
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "	lower(call_block_name) like :search ";
		$sql_search .= "	or lower(call_block_number) like :search ";
		$sql_search .= "	or lower(call_block_description) like :search ";
		$sql_search .= ") ";

		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(*) from v_call_block ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (isset($sql_search)) {
		$sql .= "and ".$sql_search;
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'call_block_number');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<b style='float: left;'>".$text['title-call-block']." (".$num_rows.")</b>\n";
	if (permission_exists('call_block_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'link'=>'call_block_edit.php']);
	}
	if (permission_exists('call_block_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'onclick'=>"if (confirm('".$text['confirm-copy']."')) { list_action_set('copy'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('call_block_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'onclick'=>"if (confirm('".$text['confirm-toggle']."')) { list_action_set('toggle'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('call_block_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"if (confirm('".$text['confirm-delete']."')) { list_action_set('delete'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	/*
	if (permission_exists('call_block_all')) {
		if ($_GET['show'] == 'all') {
			echo "	<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	*/
	echo "<form id='form_search' class='inline' method='get'>\n";
	echo "<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_block.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo "<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "</form>\n";
	echo "</div>\n";

	echo $text['description-call-block']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th class='checkbox'>\n";
	echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' value='' onclick='list_all_toggle();'>\n";
	echo "	</th>\n";
	echo th_order_by('call_block_number', $text['label-number'], $order_by, $order);
	echo th_order_by('call_block_name', $text['label-name'], $order_by, $order);
	echo th_order_by('call_block_count', $text['label-count'], $order_by, $order, '', "class='center'");
	echo th_order_by('call_block_action', $text['label-action'], $order_by, $order);
	echo th_order_by('call_block_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('date_added', $text['label-date-added'], $order_by, $order);
	echo "	<th class='hide-md-dn'>".$text['label-description']."</th>\n";
	if (permission_exists('call_block_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result)) {
		$x = 0;
		foreach($result as $row) {
			if (permission_exists('call_block_edit')) {
				$list_row_url = "call_block_edit.php?id=".urlencode($row['call_block_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			echo "	<td class='checkbox'>\n";
			echo "		<input type='checkbox' name='call_blocks[".$x."][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
			echo "		<input type='hidden' name='call_blocks[".$x."][call_block_uuid]' value='".escape($row['call_block_uuid'])."' />\n";
			echo "	</td>\n";
			echo "	<td>";
			if (permission_exists('call_block_edit')) {
				echo "<a href='".$list_row_url."'>".escape($row['call_block_number'])."</a>";
			}
			else {
				echo escape($row['call_block_number']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['call_block_name'])."</td>\n";
			echo "	<td class='center'>".escape($row['call_block_count'])."</td>\n";
			echo "	<td>".escape($row['call_block_action'])."</td>\n";
			echo "	<td class='no-link center'>";
			echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['call_block_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			echo "	</td>\n";
			echo "	<td>".date("j M Y H:i:s".(defined('TIME_24HR') && TIME_24HR == 1 ? 'a' : null), $row['date_added'])."</td>\n";
			echo "	<td class='description overflow hide-md-dn'>".escape($row['call_block_description'])."</td>\n";
			if (permission_exists('call_block_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>