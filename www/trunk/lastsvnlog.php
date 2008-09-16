<?php

	// Do not put this in the web root, it could overwrite an arbitrary file!!
	if (preg_match ("/www|public_html/", __FILE__))
		die ("Do not run from web root!!");

	define(LASTLOG, $argv[0]);

	// Get the most recent log
	$mostRecentLog = prettify_log(get_svn_log("HEAD", $revMostRecent)); 
	$mostRecent_1  = prettify_log(get_svn_log($revMostRecent - 1, $dummy));
	$mostRecent_2  = prettify_log(get_svn_log($revMostRecent - 2, $dummy));

	$lastWeek = date("Y-m-d", time() - (7 * 24 * 60 * 60));
	$lastWeekLog = get_svn_log("{" . $lastWeek . "}", $revLastWeek);

	$numRevisions[] = "<p>There were " . ($revMostRecent - $revLastWeek) . " commits in the last 7 days. Most recent:</p>";
	$output = array_merge($numRevisions, $mostRecentLog, $mostRecent_1, $mostRecent_2);
	fwrite_array(LASTLOG, $output);

	
	function fwrite_array($filename, $arr)
	{
		$fHandle = fopen($filename, "wt");
		foreach($arr as $line) fwrite($fHandle, "$line\n");
		fclose($fHandle);
	}

	function get_svn_log($revision, &$revNo)
	{
		exec("/usr/bin/svn --config-dir=/var/empty log -r $revision http://phc.googlecode.com/svn", $log, $return);
		if($return != 0)
			die("Could not run <tt>svn</tt>");

		// Extract revision number
		$dateLine = $log[1];
		$bits = explode(" | ", $dateLine);
		$revNo = substr($bits[0], 1);
		
		return $log;
	}

	function prettify_log($log)
	{
		// Remove the ---- lines
		unset($log[0]);
		array_pop($log);

		foreach($log as $key => $line)
		{
			$log[$key] = htmlentities($log[$key]);
		}

		// Highlight the date in bold
		$dateLine = $log[1];
		$bits = explode(" | ", $dateLine);
		$lastRevision = substr($bits[0], 1);
		$bits[2] = "<b>" . $bits[2] . "</b>";
		$log[1] = "<p>" . implode(" | ", $bits) . "</p><p>";
		$log[] = "</p>";

		// Replace blank lines by <br>
		foreach($log as $key => $line)
		{
			if($line == "") $log[$key] = "</p><p>";
		}

		return $log;	
	}

?>
