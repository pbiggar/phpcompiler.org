<?php
	if(strstr($_SERVER["PATH_INFO"], ".."))
		die("I don't think so");

	if(substr($_SERVER["PATH_INFO"], 0, 5) == "/img/")
	{
		header("Content-type: image/" . substr($_SERVER["PATH_INFO"], -3));
		readfile("manual_src/$_SERVER[PATH_INFO]");
	}
	else
	{
		include "header.html";
		include "manual_src/$_SERVER[PATH_INFO]";
	}
?>
