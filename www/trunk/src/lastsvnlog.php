<?php
	error_reporting(E_STRICT);
	date_default_timezone_set('Europe/Dublin');

	/*
	 * Configuration
	 */

	$CONFIG[svn]          = "/usr/bin/svn";
	$CONFIG[repo]         = "http://phc.googlecode.com/svn";
	$CONFIG[svnExtraArgs] = "--config-dir=/var/empty";
	$CONFIG[showRevNum]   = 7;

	/*
	 * Definition of an svn log
	 */

	class SvnLog
	{
		public $entries;

		function SvnLog()
		{
			$this->entries = array();
		}

		function prettyHTML($out)
		{
			foreach(array_reverse($this->entries) as $entry)
			{
				fwrite($out, "<p>\n");
				$entry->prettyHTML($out);
				fwrite($out, "</p>\n");
			}
		}

		function mostRecent()
		{
			// We assume that svn returns the logs in ascending order
			return end($this->entries)->revision;
		}
	}

	class SvnLogEntry
	{
		public $revision;
		public $author = NULL;
		public $date = NULL;
		public $paths = NULL;
		public $msg = NULL;

		function SvnLogEntry($revision)
		{
			$this->revision = $revision;
		}

		function prettyHTML($out)
		{
			$branch = $this->getBranch();
	
			/*
			 * Date format used by svn (http://www.w3.org/TR/NOTE-datetime)
			 * 
			 *   YYYY-MM-DDThh:mm:ss.sTZD
			 *
			 * where
			 *
			 * YYYY = four-digit year
			 * MM   = two-digit month (01=January, etc.)
			 * DD   = two-digit day of month (01 through 31)
			 * hh   = two digits of hour (00 through 23) (am/pm NOT allowed)
			 * mm   = two digits of minute (00 through 59)
			 * ss   = two digits of second (00 through 59)
			 * s    = one or more digits representing a decimal fraction of a second
			 * TZD  = time zone designator (Z or +hh:mm or -hh:mm)
       *
			 * Unfortunately, this date cannot be parsed automatically by PHP4 
			 * so we do it manually.
			 */

			preg_match('/^(.*)T(.*)\..*$/', $this->date, $bits);
			list($_, $date, $time) = $bits;

			/*
			 * Highlight string inserts non-breaking spaces in the "HTML" part of the message,
			 * which makes it impossible for the browser to break lines. Remove these.
			 */

			$highlighted = highlight_string($this->msg, True);
			$highlighted = str_replace("&nbsp;", " ", $highlighted);

			fwrite($out, "<b>r$this->revision</b> | <b>$this->author</b> | <b>$date $time</b> | <b>$branch</b>\n");
			fwrite($out, "<br><br>\n");
			fwrite($out, $highlighted);
		}

		function getBranch()
		{
			if($this->paths === NULL)
				return "unknown branch";

			$branches = array();
			foreach($this->paths as $path)
			{
				assert(substr($path->filename, 0, 1) === "/");
				$bits = explode("/", substr($path->filename, 1));
		
				if(count($bits) == 1)
				{
					$branches[] = $bits[0];
				}
				else
				{
					assert(count($bits) > 1);

					switch($bits[0])
					{
						case "trunk":
							$branches[] = "trunk";
							break;
						case "branches":
							$branches[] = $bits[1];
							break;
						case "tags":
							$branches[] = $bits[1];
							break;
						default:
							// In the other (unknown) cases, we return the first part of the path
							// echo "Warning: unknown branch \"$path->filename\"\n";
							$branches[] = $bits[0];
							break;
					}
				}
			}

			return implode(", ", array_unique($branches));
		}
	}

	class SvnPath
	{
		public $action;
		public $filename = NULL;

		function SvnPath($action)
		{
			$this->action = $action;
		}
	}

	/*
	 * Interact with svn
	 */

	function getSvnLog($revs)
	{
		global $CONFIG;

		exec("$CONFIG[svn] $CONFIG[svnExtraArgs] --xml -v log -r $revs $CONFIG[repo]", $log, $return);
		if($return != 0)
			die("Could not run <tt>svn</tt>");

		$parser = new SvnLogParser();
		foreach($log as $line)
		{
			$parser->parse($line, False);
		}
		$parser->parse("", True);

		return $parser->getLog();
	}

	class SvnLogParser
	{
		protected $log = NULL;
		protected $parser;
		protected $topElement;

		function SvnLogParser()
		{
			$this->parser = xml_parser_create();
			xml_set_object($this->parser, $this);
			xml_set_element_handler($this->parser, "startElementHandler", "endElementHandler");
			xml_set_character_data_handler($this->parser, "characterDataHandler");
			xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		}

		function parse($data, $final)
		{
			if(!xml_parse($this->parser, $data, $final))
				die(sprintf("XML error: %s at line %d",
					xml_error_string(xml_get_error_code($this->parser)),
					xml_get_current_line_number($this->parser)));
		}
		
		function getLog()
		{
			$this->parse("", True);
			return $this->log;
		}

		function startElementHandler($parser, $name, $attrs)
		{
			$this->topElement = $name;

			switch($name)
			{
				case "log":
					assert($this->log === NULL);
					$this->log = new SvnLog();
					break;
				case "logentry":
					array_push($this->log->entries, new SvnLogEntry($attrs["revision"]));
					break;
				case "paths":
					$entry = end($this->log->entries);
					assert($entry->paths === NULL);
					$entry->paths = array();
					break;
				case "path":
					$paths =& end($this->log->entries)->paths;
					array_push($paths, new SvnPath($attrs["action"]));
					break;
				default:
					// Unknown tag. Ignore for future compatability
					break;
			}
		}

		function endElementHandler($parser, $name)
		{
			$this->topElement = NULL;
		}

		function characterDataHandler($parser, $data)
		{
			switch($this->topElement)
			{
				case "author":
					$entry = end($this->log->entries);
					$entry->author .= $data;
					break;
				case "date":
					$entry = end($this->log->entries);
					$entry->date .= $data;
					break;
				case "path":
					$entry = end($this->log->entries);
					$path  = end($entry->paths);
					$path->filename .= $data;
					break;
				case "msg":
					$entry = end($this->log->entries);
					$entry->msg .= $data;
					break;
				default:
					// Unknown top element. Ignore for future compatability
					break;
			}
		}
	}

	/*
	 * Main
	 */
	
	$lastWeek    = date("Y-m-d", time() - (7 * 24 * 60 * 60));

	$head        = getSvnLog("HEAD");
	$lastWeek    = getSvnLog("{" . $lastWeek . "}");

	$headRev     = $head->mostRecent();
	$lastWeekRev = $lastWeek->mostRecent();
	$headRev_    = ($headRev + 1 - $CONFIG[showRevNum] > 0) 
										? ($headRev + 1 - $CONFIG[showRevNum]) 
										: 1;

	$recent      = getSvnLog("$headRev_:$headRev");

	echo "<p>There were " . ($head->mostRecent() - $lastWeek->mostRecent()) . " commits in the last 7 days. Most recent:</p>";
	$recent->prettyHTML(STDOUT);
?>
<?php // vi:set noexpandtab: ?>
