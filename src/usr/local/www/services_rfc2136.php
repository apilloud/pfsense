<?php
/* $Id$ */
/*
	services_rfc2136.php

	Copyright (C) 2008 Ermal Luçi
	Copyright (C) 2013-2015 Electric Sheep Fencing, LP
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/
/*
	pfSense_MODULE:	dnsupdate
*/

##|+PRIV
##|*IDENT=page-services-rfc2136clients
##|*NAME=Services: RFC 2136 clients page
##|*DESCR=Allow access to the 'Services: RFC 2136 clients' page.
##|*MATCH=services_rfc2136.php*
##|-PRIV

require("guiconfig.inc");

if (!is_array($config['dnsupdates']['dnsupdate'])) {
	$config['dnsupdates']['dnsupdate'] = array();
}

$a_rfc2136 = &$config['dnsupdates']['dnsupdate'];

if ($_GET['act'] == "del") {
	unset($a_rfc2136[$_GET['id']]);

	write_config();

	header("Location: services_rfc2136.php");
	exit;
}
else if ($_GET['act'] == "toggle") {
	if ($a_rfc2136[$_GET['id']]) {
		if (isset($a_rfc2136[$_GET['id']]['enable'])) {
			unset($a_rfc2136[$_GET['id']]['enable']);
		} else {
			$a_rfc2136[$_GET['id']]['enable'] = true;
		}
		write_config();
				
		header("Location: services_rfc2136.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"), gettext("RFC 2136 clients"));
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("DynDns"), false, "services_dyndns.php");
$tab_array[] = array(gettext("RFC 2136"), true, "services_rfc2136.php");
display_top_tabs($tab_array);

if ($input_errors)
    print_input_errors($input_errors);
?>

<form action="services_rfc2136.php" method="post" name="iform" id="iform">
	<div class="table-responsive">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
        		    <th><?=gettext("If")?></th>
        		    <th><?=gettext("Server")?></th>
        		    <th><?=gettext("Hostname")?></th>
        		    <th><?=gettext("Cached IP")?></th>
        		    <th><?=gettext("Description")?></th>
		            <th></th>
		        </tr>
		    </thead>
		    <tbody>
<?php


$iflist = get_configured_interface_with_descr();

$i = 0;
foreach ($a_rfc2136 as $rfc2136):
?>
		        <tr <?=(isset($rfc2136['enable']) ? '' : 'class="disabled"')?>">
		            <td>
<?php
	foreach ($iflist as $if => $ifdesc) {
	    if ($rfc2136['interface'] == $if) {
	        print($ifdesc);
			break;
	    }
	}
?>
		            </td>
		            <td>
		                <?=htmlspecialchars($rfc2136['server'])?>
		            </td>
		            <td>
		                <?=htmlspecialchars($rfc2136['host'])?>
		            </td>
		            <td>
<?php
	$filename = "{$g['conf_path']}/dyndns_{$rfc2136['interface']}_rfc2136_" . escapeshellarg($rfc2136['host']) . "_{$rfc2136['server']}.cache";

	if (file_exists($filename)) {
		print('IPv4: ');
		if (isset($rfc2136['usepublicip']))
			$ipaddr = dyndnsCheckIP($rfc2136['interface']);
		else
			$ipaddr = get_interface_ip($rfc2136['interface']);

		$cached_ip_s = explode("|", file_get_contents($filename));
		$cached_ip = $cached_ip_s[0];

		if ($ipaddr != $cached_ip)
			print('<font color="red">');
		else
			print('<font color="green">');

		print(tmlspecialchars($cached_ip));
		print('</font>');
	} else {
		print('IPv4: N/A');
	}

	print('<br />');

	if (file_exists("{$filename}.ipv6")) {
		print('IPv6: ');
		$ipaddr = get_interface_ipv6($rfc2136['interface']);
		$cached_ip_s = explode("|", file_get_contents("{$filename}.ipv6"));
		$cached_ip = $cached_ip_s[0];

		if ($ipaddr != $cached_ip)
			print('<font color="red">');
		else
			print('<font color="green">');

		print(htmlspecialchars($cached_ip));
		print('</font>');
	} else {
		print('IPv6: N/A');
	}

?>
			</td>
			<td>
				<?=htmlspecialchars($rfc2136['descr'])?>
			</td>
			<td>
				<a class="fa fa-pencil"		title="<?=gettext('Edit client')?>"	href="services_rfc2136_edit.php?id=<?=$i?>"></a>
			<?php if (isset($rfc2136['enable'])) {
			?>	
				<a  class="fa fa-ban"		title="<?=gettext('Disable client')?>"	href="?act=toggle&amp;id=<?=$i?>"></a>
			<?php } else {
			?>
				<a class="fa fa-check-square-o"	title="<?=gettext('Enable client')?>"	href="?act=toggle&amp;id=<?=$i?>" ></a>
			<?php }
			?>						
				<a class="fa fa-trash"		title="<?=gettext('Delete client')?>"	href="services_rfc2136.php?act=del&amp;id=<?=$i?>" onclick="return confirm('<?=gettext("Are you sure you want to delete this client?")?>')"></a>
			</td>
			</tr>
<?php
    $i++;
endforeach; ?>

		    </tbody>
        </table>
    </div>
</form>

<nav class="action-buttons" style="margin-top: 10px;">
	<a href="services_rfc2136_edit.php" class="btn btn-sm btn-success btn-sm">
		<i class="fa fa-plus" style="font-size:15px; vertical-align: middle; margin-right: 6px;"></i>
		<?=gettext('Add')?>
	</a>
</nav>

<?php
include("foot.inc");
