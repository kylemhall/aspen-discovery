<?php

/*
 * Prompt for information about the new installation
 */

echo("This will create the proper directories and configuration files for a new site\r\n");
$sitename = '';
while (empty($sitename)) {
	$sitename = readline("Enter the sitename to be setup (i.e. demo.localhost, demo.production) > ");
}
$variables = [
	'sitename' => $sitename,
];

$operatingSystem = php_uname('s');
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	$runningOnWindows = true;
}else{
	$runningOnWindows = false;
}

$installDir = '/usr/local/aspen-discovery';
if ($runningOnWindows){
	$installDir = 'c:/web/aspen-discovery';
}
$siteDir = $installDir . '/sites/' . $sitename;

if (file_exists($siteDir)){
	$clearExisting = readline ("The site directory already exists, do you want to remove the existing configuration (y/N)? ");
	if (empty($clearExisting) || ($clearExisting != 'Y' && $clearExisting != 'y')){
		die();
	}else{
		$continue = readline("REMOVING EXISTING CONFIGURATION, continue (y/N)? ");
		if (empty($continue) || ($continue != 'Y' && $continue != 'y')){
			die();
		}else{
			recursive_rmdir($siteDir);
		}
	}
}

//Prompt for needed information
$variables['library'] = '';
while (empty($variables['library'])) {
	$variables['library'] = readline("Enter the library or consortium name i.e. Aspen Library > ");
}

$variables['title'] = '';
while (empty($variables['title'])) {
	$variables['title'] = readline("Enter the title of the site i.e. Aspen Demo > ");
}

$variables['url'] = '';
while (empty($variables['url'])) {
	$variables['url'] = readline("Enter the url where the site will be accessed i.e. https://aspen.turningleaftechnologies.com > ");
}

$siteOnWindows = readline("Will the site run on windows (y/N)? ");
if (empty($siteOnWindows) || ($siteOnWindows != 'Y' && $siteOnWindows != 'y')){
	$siteOnWindows = false;
}else{
	$siteOnWindows = true;
}

$variables['solrPort'] = readline("Which port should solr run on (8080)? ");
if (empty($variables['solrPort'])){
	$variables['solrPort'] = "8080";
}

$variables['ils'] = readline("Which ils does the library use (Koha)? ");
if (empty($variables['ils'])){
	$variables['ils'] = "Koha";
}

if ($variables['ils'] = 'Koha'){
	$variables['ilsDriver'] = 'Koha';
	$variables['ilsDBHost'] = '';
	while (empty($variables['ilsDBHost'])) {
		$variables['ilsDBHost'] = readline("Database Host for the Koha Database  > ");
	}
	$variables['ilsDBName'] = '';
	while (empty($variables['ilsDBName'])) {
		$variables['ilsDBName'] = readline("Database name for the Koha Database  > ");
	}
	$variables['ilsDBUser'] = '';
	while (empty($variables['ilsDBUser'])) {
		$variables['ilsDBUser'] = readline("Database Username for the Koha Database  > ");
	}
	$variables['ilsDBPwd'] = '';
	while (empty($variables['ilsDBPwd'])) {
		$variables['ilsDBPwd'] = readline("Database Password for the Koha Database  > ");
	}
	$variables['ilisDBPort'] = '';
	while (empty($variables['ilisDBPort'])) {
		$variables['ilisDBPort'] = readline("Database Port for the Koha Database  > ");
	}
	$variables['ilsDBTimezone'] = readline("Database timezone for the Koha Database (US/Pacific) > ");
	if (empty($variables['ilsDBTimezone'])){
		$variables['ilsDBTimezone'] = 'US/Pacific';
	}
	$variables['ilsClientId'] = readline("Client Id for the Koha API > ");
	$variables['ilsClientSecret'] = readline("Client Secret for the Koha API > ");
}
while (empty($variables['ilsDriver'])) {
	$variables['ilsDriver'] = readline("Enter the Aspen Driver for the ILS  > ");
}

$variables['ilsUrl'] = '';
while (empty($variables['ilsUrl'])) {
	$variables['ilsUrl'] = readline("Enter the url of the OPAC for the ILS  > ");
}

//This can be blank
$variables['staffUrl'] = readline("Enter the url of the staff client for the ILS  > ");

$variables['aspenDBName'] =  readline("Enter the name of the database for aspen (aspen) > ");
if (empty($variables['aspenDBName'])){
	$variables['aspenDBName'] = "aspen";
}

$variables['aspenDBUser'] =  readline("Enter the user name of the database for aspen (root) > ");
if (empty($variables['aspenDBUser'])){
	$variables['aspenDBUser'] = "root";
}

$variables['aspenDBPwd'] = '';
while (empty($variables['aspenDBPwd'])) {
	$variables['aspenDBPwd'] = readline("Enter the password for {$variables['aspenDBUser']} in the aspen database > ");
}

$variables['timezone'] =  readline("Enter the timezone of the library (America/Los_Angeles) > ");
if (empty($variables['timezone'])){
	$variables['timezone'] = "America/Los_Angeles";
}

$variables['aspenAdminPwd'] = '';
while (empty($variables['aspenAdminPwd'])) {
	$variables['aspenAdminPwd'] = readline("Enter the password for the aspen_admin user on this server > ");
}

/*
 * Setup the server
 */

//Create the basic sites directory
if ($siteOnWindows){
	recursive_copy($installDir . '/sites/template.windows', $siteDir);
}else{
	recursive_copy($installDir . '/sites/template.linux', $siteDir);
}

//Rename files appropriately based on the sitename
rename($siteDir . '/httpd-{sitename}.conf', $siteDir . "/httpd-{$sitename}.conf");
if ($siteOnWindows){
	rename($siteDir . '/{sitename}.bat', $siteDir . "/{$sitename}.bat");
}else{
	rename($siteDir . '/{sitename}.sh', $siteDir . "/{$sitename}.sh");
}
rename($siteDir . '/conf/config.pwd.ini.template', $siteDir . "/conf/config.pwd.ini");

replaceVariables($siteDir . "/httpd-{$sitename}.conf", $variables);
if ($siteOnWindows) {
	replaceVariables($siteDir . "/{$sitename}.bat", $variables);
}else{
	replaceVariables($siteDir . "/{$sitename}.sh", $variables);
}
replaceVariables($siteDir . "/conf/config.ini", $variables);
replaceVariables($siteDir . "/conf/config.pwd.ini", $variables);

if (!$siteOnWindows){
	exec('sudo timedatectl set-timezone "'. $variables['timezone'] . '"');
}

//Import the database
if ($clearExisting) {
	echo("Removing existing database\r\n");
	exec("mysql -u{$variables['aspenDBUser']} -p\"{$variables['aspenDBPwd']}\" -e\"DROP DATABASE IF EXISTS {$variables['aspenDBName']}\"");
}
echo("Creating database\r\n");
exec("mysql -u{$variables['aspenDBUser']} -p\"{$variables['aspenDBPwd']}\" -e\"CREATE DATABASE {$variables['aspenDBName']}\"");
echo("Loading default database\r\n");
exec("mysql -u{$variables['aspenDBUser']} -p\"{$variables['aspenDBPwd']}\" {$variables['aspenDBName']} < $installDir/install/aspen.sql");

//Connect to the database
$aspen_db = new PDO("mysql:dbname={$variables['aspenDBName']};host=127.0.0.1",$variables['aspenDBUser'],$variables['aspenDBPwd']);
$updateUserStmt = $aspen_db->prepare("UPDATE user set cat_password=" . $aspen_db->quote($variables['aspenAdminPwd']) . ", password=" . $aspen_db->quote($variables['aspenAdminPwd']) . " where cat_username = 'aspen_admin'");
$updateUserStmt->execute();

if ($variables['ils'] == 'Koha'){
	echo("Loading Koha information to database\r\n");
	copy("$installDir/install/koha_connection.sql", "/tmp/koha_connection_$sitename.sql");
	replaceVariables("/tmp/koha_connection_$sitename.sql", $variables);
	exec("mysql -u{$variables['aspenDBUser']} -p\"{$variables['aspenDBPwd']}\" {$variables['aspenDBName']} < /tmp/koha_connection_{$sitename}.sql");
}

$aspen_db = null;

//Make data directories
echo("Setting up data and log directories\r\n");
$dataDir = '/data/aspen-discovery/' . $sitename;
if (!file_exists($dataDir)){
	mkdir($dataDir, 0770, true);
	if (!$runningOnWindows){
		exec('chown -R apache:apache ' . $dataDir);
	}
}
if (!file_exists('/data/aspen-discovery/accelerated_reader')){
	mkdir('/data/aspen-discovery/accelerated_reader', 0770, true);
}
recursive_copy($installDir . '/data_dir_setup', $dataDir);
if (!$runningOnWindows){
	exec('chown -R apache:apache ' . $dataDir . '/ils');
}

//Make files directory writeable
if (!$runningOnWindows){
	exec('chown -R apache:apache ' . $installDir . '/code/web/files');
	exec('chmod -R 755 ' . $installDir . '/code/web/files');
}

//Make log directories
$logDir = '/var/log/aspen-discovery/' . $sitename;
if (!file_exists($logDir)){
	mkdir($logDir, 0770, true);
	if (!$runningOnWindows){
		exec('chown -R apache:apache ' . $logDir);
	}
}

//Link the httpd conf file
if (!$siteOnWindows){
	symlink($siteDir . "/httpd-{$sitename}.conf", "/etc/httpd/conf.d/httpd-{$sitename}.conf");
}

//Setup solr
if (!$siteOnWindows){
	exec("adduser solr");
	exec('chown -R apache:apache ' . $installDir . '/sites/default/solr-7.6.0');
	exec('chown -R apache:apache ' . $dataDir . '/solr7');
}

if ($siteOnWindows){
	//Start solr
	chdir($siteDir);
	execInBackground( "{$sitename}.bat");
}else{
	exec("apache ctl restart");
	//Start solr
	execInBackground($siteDir . "/{$sitename}.sh");
}

echo("\r\n");
echo("\r\n");
echo("-------------------------------------------------------------------------\r\n");
echo("Next Steps\r\n");
$step = 1;
if ($siteOnWindows) {
	echo($step++ . ") Add Include \"$siteDir/httpd-{$sitename}.conf\" to the httpd.conf file\r\n");
	$servername = preg_replace('~https?://~', '', $variables['url']);
	echo($step++ . ") Add $servername to the hosts file\r\n");
	echo($step++ . ") Restart apache\r\n");
}
echo($step++ . ") Login to the server as aspen_admin and run database updates\r\n");
echo($step++ . ") Setup library(ies) within the admin interface\r\n");
echo($step++ . ") Setup location(s) within the admin interface\r\n");
echo($step++ . ") Start initial index\r\n");

exit();

function recursive_copy($src,$dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recursive_copy($src .'/'. $file, $dst .'/'. $file);
			}
			else {
				copy($src .'/'. $file,$dst .'/'. $file);
			}
		}
	}
	closedir($dir);
}

function recursive_rmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (is_dir($dir."/".$object) && !is_link($dir."/".$object))
					recursive_rmdir($dir."/".$object);
				else
					unlink($dir."/".$object);
			}
		}
		rmdir($dir);
	}
}

function replaceVariables($filename, $variables){
	$contents = file ($filename);
	$fhnd = fopen($filename, 'w');
	foreach ($contents as $line){
		foreach ($variables as $name => $value){
			$line = str_replace('{' . $name . '}', $value, $line);
		}
		fwrite($fhnd, $line);
	}
	fclose($fhnd);
}

function execInBackground($cmd) {
	echo ("Running $cmd\r\n");
	if (substr(php_uname(), 0, 7) == "Windows"){
		$cmd = str_replace('/', '\\', $cmd);
		pclose(popen("start /B ". $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}