<?php
/* $Id$ */
/*
	system_firmware_check.php
*/
/* ====================================================================
 *	Copyright (c)  2004-2015  Electric Sheep Fencing, LLC. All rights reserved.
 *  Originally from m0n0wall, copyright 2004 Manuel Kasper (BSD 2 clause)
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
	pfSense_MODULE: firmware
*/

##|+PRIV
##|*IDENT=page-system-firmware-autoupdate
##|*NAME=System: Firmware: Auto Update page
##|*DESCR=Allow access to the 'System: Firmware: Auto Update' page.
##|*MATCH=system_firmware_check.php*
##|-PRIV

$d_isfwfile = 1;
require("guiconfig.inc");
require_once("pfsense-utils.inc");

$curcfg = $config['system']['firmware'];
$pgtitle = array(gettext("System"), gettext("Firmware"), gettext("Auto Update"));
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Manual Update"), false, "system_firmware.php");
$tab_array[] = array(gettext("Auto Update"), true, "system_firmware_check.php");
$tab_array[] = array(gettext("Updater Settings"), false, "system_firmware_settings.php");
if($g['hidedownloadbackup'] == false)
	$tab_array[] = array(gettext("Restore Full Backup"), false, "system_firmware_restorefullbackup.php");
display_top_tabs($tab_array);
?>

<form action="system_firmware_auto.php" method="post">
	<div id="statusheading" class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext('Update progress')?></h2></div>
		<div class="panel-body" name="output" id="output"></div>
	</div>

	<div id="backupdiv" style="visibility:hidden">
		<?php if ($g['hidebackupbeforeupgrade'] === false): ?>
		<br /><input type="checkbox" name="backupbeforeupgrade" id="backupbeforeupgrade" /><?=gettext("Perform full backup prior to upgrade")?>
		<?php endif; ?>
	</div>
	<br />
	<input id='invokeupgrade' class="btn btn-warning" style='visibility:hidden' type="submit" value="<?=gettext("Invoke Auto Upgrade"); ?>" />

<?php

/* Define necessary variables. */
if (isset($curcfg['alturl']['enable'])) {
	$updater_url = "{$config['system']['firmware']['alturl']['firmwareurl']}";
} else {
	$updater_url = $g['update_url'];
}
$needs_system_upgrade = false;
$static_text .= gettext("Downloading new version information...");

$nanosize = "";
if ($g['platform'] == "nanobsd") {
	if (!isset($g['enableserial_force'])) {
		$nanosize = "-nanobsd-vga-";
	} else {
		$nanosize = "-nanobsd-";
	}

	$nanosize .= strtolower(trim(file_get_contents("/etc/nanosize.txt")));
}

if (download_file_with_progress_bar("{$updater_url}/version{$nanosize}", "/tmp/{$g['product_name']}_version", 'read_body', 5, 5) === true) {
	$remote_version = trim(@file_get_contents("/tmp/{$g['product_name']}_version"));
}
$static_text .= gettext("done") . "\\n";
if (!$remote_version) {
	$static_text .= gettext("Unable to check for updates.") . "\\n";
	if (isset($curcfg['alturl']['enable'])) {
		$static_text .= gettext("Could not contact custom update server.") . "\\n";
	} else {
		$static_text .= sprintf(gettext('Could not contact %1$s update server %2$s%3$s'), $g['product_name'], $updater_url, "\\n");
	}
} else {
	$static_text .= gettext("Obtaining current version information...");
	panel_text($static_text);

	$current_installed_buildtime = trim(file_get_contents("/etc/version.buildtime"));

	$static_text .= "done<br />";
	panel_text($static_text);

	if (pfs_version_compare($current_installed_buildtime, $g['product_version'], $remote_version) == -1) {
		$needs_system_upgrade = true;
	} else {
		$static_text .= "<br />" . gettext("You are on the latest version.") . "<br />";
		panel_text($static_text);
		panel_heading_class('success');
	}
}

update_output_window($static_text);
if ($needs_system_upgrade == false) {
	print("</form>");
	require("foot.inc");

	exit;
}
?>
<script>
	events.push(function(){
		$('#invokeupgrade').css('visibility','visible');
		$('#backupdiv').css('visibility','visible');
	});
</script>
<?php

$txt  = gettext("A new version is now available") . "<br />";
$txt .= gettext("Current version") .": ". $g['product_version'] . "<br />";
if ($g['platform'] == "nanobsd") {
	$txt .= "  " . gettext("NanoBSD Size") . " : " . trim(file_get_contents("/etc/nanosize.txt")) . "<br />";
}
$txt .= "	   " . gettext("Built On") .": ".  $current_installed_buildtime . "<br />";
$txt .= "	" . gettext("New version") .": ".  htmlspecialchars($remote_version, ENT_QUOTES | ENT_HTML401). "<br /><br />";
$txt .= "  " . gettext("Update source") .": ".	$updater_url . "<br />";
panel_text($txt);
panel_heading_class('info');
?>

</form>
<?php

// Update the class of the message panel so that it's color changes
// Use danger, success, info, warning, default etc
function panel_heading_class($newclass = 'default') {
?>
	<script>
	events.push(function(){
		$('#statusheading').removeClass().addClass('panel panel-' + '<?=$newclass?>');
	});
	</script>
<?php
}

// Update the text in the panel-heading
function panel_text($text) {
?>
	<script>
	events.push(function(){
		$('#output').html('<?=$text?>');
	});
	</script>
<?php
}

include("foot.inc");
