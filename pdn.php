<!DOCTYPE html>
<html>
<head>

<?php
/* 
WRITTEN by Cass Barbour

	My brother and I were collaborating via an FTP server and were finding file 
	navigation cumbersome without an FTP connection. We had SO many projects going on 
	that remembering the URL where a file was stored was getting impossible.
	
	Apache DirectoryListing was somewhat helpful, but not really---it would automatically 
	load any index(.html/.php) pages instead of listing a directory, and it wouldn't 
	give us any file information (dates, file sizes, etc). We decided that we needed a 
	better way to browse the server via the web.
	
	So, for fun, I decided to build my own file navigator in PHP. This navigator 
	allows you to browse a entire series of directories starting with wherever you 
	drop this file. You cannot go "up" from the current directory, you can only delve 
	downwards. Using the file is as simple as dragging it into any directory on a server 
	and loading it.
	
	I've found the file to be useful in a large number of ways. But for security reasons, 
	I only ever let it live on a server temporarily; with a simple hard-coded 
	password, this file could be secured and used ad infinitum. 

		
	In addition, this file is also a lot more convenient to drop onto a server for a 
	quick check, rather than to modify the Apache .htaccess file to gain 
	DirectoryListing capabilities.


Notes:
	1. Lists all files/folders within current folder except ../ and ./ and this PHP file.  
	2. Links to files or folders and gives info on each one.  Denies access 
		to ./ and ../ from current directory and files on other servers.  It ONLY 
		allows navigating from the current folder down.
	
*/

$version = "0.22";

$directory = $_GET['dir'];  // get directory from address bar
if( $directory == "" || substr_count( $directory, "./" ) ) { $path = getcwd(); $directory = ""; } else { $path = $directory; }
	// if no address in address bar or address trying to access previous directories, get current folder


?>
<title><?php echo "Directory Contents: {$directory}"; ?></title>
	<style type="text/css">
		body { padding: 0px; margin: 2%; color: #000; background-color: #fff; font-family: tahoma, arial; font-size: 10pt;  }

		table { width: 100%; background-color: #fff; border-bottom: 2px solid #0431B4; }
		td { font-size: 10pt; border-bottom: 1px solid #ccc; padding-top: 7px; padding-bottom: 7px;}
		td:first-child { width: 50px; text-align: center; border-right: 1px solid #ccc; background-color: #f0f0f0; font-size: 9pt; }

		thead td { background-color: #0080FF; color: #fff; font-size: 12pt; border-bottom: 3px solid #0431B4; }
		thead td a { color: #fff; }
		thead td:first-child { 
			background-color: #0080FF; 
			border-right: 0px;
			-webkit-border-top-left-radius: 5px;
			-moz-border-radius-topleft: 5px;
			border-top-left-radius: 5px;
		}
		thead td:last-child {
			-webkit-border-top-right-radius: 5px;
			-moz-border-radius-topright: 5px;
			border-top-right-radius: 5px;
		}
		

		tr.directory td { background-color: #ddd; font-weight: bold; border-bottom: 1px solid #fff; }
		tr:hover td { background-color: #eee; }
		thead:hover td { background-color: #0080FF; }


		.fc__777 { color: #777; }

		.italics { font-style: italic; }
		.bold { font-weight: bold; }

		.center { text-align: center; }
		.right { text-align: right; }

		p, h1, h2, h3, h4, h5, span { padding-left: 5px; padding-top: 5px; color: #0431B4; }
		p { color: #000; }
	</style>
</head>
<body>

<?php	



if( ! is_dir( $path ) ) { ?>

	<h3>Oops. No directory of that name was found.</h3>
	<p><a href="<?php echo end( explode( "/", __FILE__ ) ); ?>">Go back</a></p>

<?php 
} else {
	
	// Create Directory Navigation
	$directory_navigation_bar = "<em class=\"fc__777\">Current location:</em> ";
	$this_filepath_array = explode( "/", dirname(__FILE__) );
	$this_dir = $this_filepath_array[(count($this_filepath_array)-1)];
	unset( $this_dir_array );
	
	if( $directory != "" ) { 

		$directory_exploded = explode( "/", $directory );

		foreach($directory_exploded as $key => $value) {
			$whileCounter = 0;

			$makePreDirName = "";
			while( $whileCounter <= $key - 1 ) { // returns up to but not including last /xyz/
				$makePreDirName .= $directory_exploded[$whileCounter] . "/";
				$whileCounter++;
			}
			$makePreDirName = substr( $makePreDirName, 0, strlen( $makePreDirName ) - 1 );
			$directory_navigation_bar .= "&nbsp;<a href='{$thisFileName}?dir={$makePreDirName}'>"; 

			if( 0 > $whileCounter-1  ) { $directory_navigation_bar .= $this_dir; } else { $directory_navigation_bar .= "{$directory_exploded[$whileCounter-1]}"; }
			$directory_navigation_bar .= "/</a>";
		}

	}
	if( is_array( $directory_exploded ) ) {
		$directory_navigation_bar .= "&nbsp;<span class='greyText'>{$directory_exploded[$whileCounter]}/</span>";
	} else {
		$directory_navigation_bar .= "&nbsp;".$this_dir."/"; 
	}






	// open the directory
	$dir = opendir( $path );
	if( $directory != "" ) { $currentLocation = $directory . ( substr( $directory, -1, 1 ) == "/" ? "" : "/" ); } else { $currentLocation = ""; }

	$currentFile = $_SERVER["PHP_SELF"];
	$parts = Explode('/', $currentFile);
	$thisFileName = $parts[count($parts) - 1];	

	$counter = 0;


	?>
	<h2>PHP Directory Navigator v<?php echo $version; ?></h2>
	<p><?php echo $directory_navigation_bar; ?></p>

	
	<table border="0" cellpadding="4" cellspacing="0">
		<thead>
			<td class="center"><?php if( isset( $whileCounter )   ) { ?><a href="<?php echo "{$thisFileName}?dir={$makePreDirName}"; ?>">&larr;</a><?php } else { echo "&nbsp;"; } ?></td>	
			<td>File</td>

			<td>Type</td>	
			<td>Date Modified</td>	

			<td class="right">Size&nbsp;</td>
		</thead>

<?php	
	$finfo = finfo_open(FILEINFO_MIME_TYPE); 
	
	// loop through the directory	
	while (false !== ($fname = readdir($dir)))  {
	  // strip the . and .. entries out
	  
		if ( $fname != ".." && $fname != "."  &&  $fname != $thisFileName ) {  // output only files/dirs, and exclude the currently executed filename from the list
			$counter++;
		
			if( $directory != "" ) {
				$proper_name = $directory . '/' . $fname;
			} else { 
				$proper_name = $fname;
			} 
			
			if( is_dir( $directory . '/' . $fname )  || is_dir( $fname ) ) { 
				$is_dir = true;
				
				$displayName = $fname . "/"; 
				$sizeClass = $fileClass = "";		
				$filesize = (iterator_count(new DirectoryIterator($proper_name))-2)." items";
			
				if( is_dir( $fname ) ) { 
					$link = "{$thisFileName}?dir={$fname}";

				} else {
					$link = "{$thisFileName}?dir={$directory}/{$fname}";
				}				
			} else { 
				if( $directory != "" ) { $file1 = $directory."/".$fname; } else { $file1 = $fname; }
				$is_dir = false;			
				$filesize = filesize( $file1 );
				$sizeClass = "borderRight";
				$fileClass = "";	

				if( $filesize > 1048576 ) { $filesize = round( $filesize / 1048576, 2 ) . " MB"; 
				} elseif ( $filesize > 1024 ) { $filesize = round( $filesize / 1024, 2 ) . " KB";
				} else { $filesize = round( $filesize, 2) . " bytes"; }
				// directory names don't need filesize, they are 4KB

				$displayName = $fname; 
				$link = $currentLocation . $fname;
				$class = "";	
			}
?>
		<tr class="<?php echo( $is_dir ? "directory" : "" ); ?>">
			<td class="table1_numColumn"><?php echo $counter; ?>&nbsp;</td>

			<td class="<?php echo $fileClass; ?>"><a href='<?php echo $link; ?>'><?php echo $displayName; ?></a>&nbsp;&nbsp;&nbsp;</td>
			<td class="<?php echo $fileClass; ?>"><?php echo finfo_file($finfo, $proper_name); ?></td>

			<td class="<?php echo $fileClass; ?>"><?php echo date( "m/d/y h:ia", filemtime( $proper_name )   )  /*( $is_dir ? "Directory" : "" ) */; ?></td>

			<td class="<?php echo $fileClass; ?> <?php echo $sizeClass; ?> right" ><?php echo $filesize; ?></td>
		</tr>
<?php
		}
	} ?>
	</table>

<?php
	// close the directory
	closedir( $dir );
?>
	<p><?php echo $directory_navigation_bar; ?></p>
<?php
 } 
?>

</body>
</html>
	