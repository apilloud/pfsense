<?php
/* $Id$ */
/*
	interfaces_wireless.php
*/
/* ====================================================================
 *	Copyright (c)  2004-2015  Electric Sheep Fencing, LLC. All rights reserved.
 *  Copyright (c)  2010 Erik Fonnesbeck
 *
 *	Redistribution and use in source and binary forms, with or without modification,
 *	are permitted provided that the following conditions are met:
 *
 *	1. Redistributions of source code must retain the above copyright notice,
 *		this list of conditions and the following disclaimer.
 *
 *	2. Redistributions in binary form must reproduce the above copyright
 *		notice, this list of conditions and the following disclaimer in
 *		the documentation and/or other materials provided with the
 *		distribution.
 *
 *	3. All advertising materials mentioning features or use of this software
 *		must display the following acknowledgment:
 *		"This product includes software developed by the pfSense Project
 *		 for use in the pfSense software distribution. (http://www.pfsense.org/).
 *
 *	4. The names "pfSense" and "pfSense Project" must not be used to
 *		 endorse or promote products derived from this software without
 *		 prior written permission. For written permission, please contact
 *		 coreteam@pfsense.org.
 *
 *	5. Products derived from this software may not be called "pfSense"
 *		nor may "pfSense" appear in their names without prior written
 *		permission of the Electric Sheep Fencing, LLC.
 *
 *	6. Redistributions of any form whatsoever must retain the following
 *		acknowledgment:
 *
 *	"This product includes software developed by the pfSense Project
 *	for use in the pfSense software distribution (http://www.pfsense.org/).
 *
 *	THIS SOFTWARE IS PROVIDED BY THE pfSense PROJECT ``AS IS'' AND ANY
 *	EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 *	PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE pfSense PROJECT OR
 *	ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 *	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *	HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 *	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 *	OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *	====================================================================
 *
 */
/*
	pfSense_MODULE: interfaces_assign
*/

##|+PRIV
##|*IDENT=page-interfaces-wireless
##|*NAME=Interfaces: Wireless page
##|*DESCR=Allow access to the 'Interfaces: Wireless' page.
##|*MATCH=interfaces_wireless.php*
##|-PRIV

require("guiconfig.inc");

if (!is_array($config['wireless'])) {
	$config['wireless'] = array();
}
if (!is_array($config['wireless']['clone'])) {
	$config['wireless']['clone'] = array();
}

$a_clones = &$config['wireless']['clone'];

function clone_inuse($num) {
	global $config, $a_clones;

	$iflist = get_configured_interface_list(false, true);

	foreach ($iflist as $if) {
		if ($config['interfaces'][$if]['if'] == $a_clones[$num]['cloneif']) {
			return true;
		}
	}

	return false;
}

if ($_GET['act'] == "del") {
	/* check if still in use */
	if (clone_inuse($_GET['id'])) {
		$input_errors[] = gettext("This wireless clone cannot be deleted because it is assigned as an interface.");
	} else {
		mwexec("/sbin/ifconfig " . $a_clones[$_GET['id']]['cloneif'] . " destroy");
		unset($a_clones[$_GET['id']]);

		write_config();

		header("Location: interfaces_wireless.php");
		exit;
	}
}


$pgtitle = array(gettext("Interfaces"), gettext("Wireless"));
$shortcut_section = "wireless";
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors); ?>

<?php
$tab_array = array();
$tab_array[] = array(gettext("Interface assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("Interface Groups"), false, "interfaces_groups.php");
$tab_array[] = array(gettext("Wireless"), true, "interfaces_wireless.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("QinQs"), false, "interfaces_qinq.php");
$tab_array[] = array(gettext("PPPs"), false, "interfaces_ppps.php");
$tab_array[] = array(gettext("GRE"), false, "interfaces_gre.php");
$tab_array[] = array(gettext("GIF"), false, "interfaces_gif.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGG"), false, "interfaces_lagg.php");
display_top_tabs($tab_array);
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-condensed">
		<thead>
			<tr>
			  <th><?=gettext("Interface"); ?></th>
			  <th><?=gettext("Mode"); ?></th>
			  <th><?=gettext("Description"); ?></th>
			  <th></th>
			</tr>
		</thead>
		<tbody>
<?php

$i = 0;

foreach ($a_clones as $clone) {
?>
			<tr>
				<td>
					<?=htmlspecialchars($clone['cloneif'])?>
				</td>
				<td>
					<?= $wlan_modes[$clone['mode']]; ?>
				</td>
				<td>
					<?=htmlspecialchars($clone['descr'])?>
				</td>
				<td>
					<a class="fa fa-pencil"	title="<?=gettext('Edit WiFi interface')?>"	href="interfaces_wireless_edit.php?id=<?=$i?>"></a>
					<a class="fa fa-trash"	title="<?=gettext('Delete WiFi interface')?>"	href="interfaces_wireless.php?act=del&amp;id=<?=$i?>" onclick="return confirm('<?=gettext("Are you sure you want to delete this WiFi interface?")?>')"></a>
				</td>
			</tr>
<?php
	$i++;
}
?>
		</tbody>
	</table>

	<nav class="action-buttons" style="margin-top: 10px;">
		<a href="interfaces_wireless_edit.php" class="btn btn-success btn-sm">
			<i class="fa fa-plus" style="font-size:15px; vertical-align: middle; margin-right: 6px;"></i>
			<?=gettext("Add")?></a>
	</nav>
</div>
<?php
include("foot.inc");
