<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/xmp.php');

//----------------------------------------------------------------------------------------


$tmpdir = dirname(__FILE__) . '/tmp';

// BioStor reference numbers
$ids = array(106150);

$start = 160400;
$end = 160484;

$start = 160300;
$end = 160399;

$start = 160000;
$end = 160299;

$start = 159500;
$end   = 159999;

$start = 159400;
$end   = 159410;
//$end   = 159499;

$start 	= 160494;
$end 	= 160494;

$start = 159414;
$end   = 159440;
//$end   = 159499;

$start = 159441;
$end   = 159499;

$start = 159000;
$start = 159177;
$start = 159360;
$end   = 159399;

// to do
$start = 158900;
$end   = 158999;

$start = 1;
$start = 45;
$end   = 100;

$start = 101;
$end   = 200;
$start = 292;
$end   = 300;

$start = 392;
$end   = 400;

$start = 401;
$end   = 500;

$start = 501;
$end   = 600;

$start = 645;
$end   = 700;

$start = 701;
$end   = 800;

$start = 921;
$end   = 1000;

$start = 160495;
$end   = 160575;

$start = 1001;
$start = 1162;
$end   = 1400;

$start = 160550;
$end = 160550;

$ids=array(987,
985,
982,
983,
984,
159134,
159136,
159137,
159138,
159135,
159139,
979,
1162,
160542,
160544,
160545,
159177,
986,
989,
160547,
160549,
13,
160538,
160541,
981,
160568,
978,
988,
980,
160540,
160548,
160539,
160543,
160546,
990,
160047,
3,
19,
20,
252,
399,
400,
401,
402,
403,
404,
405,
406,
407,
408,
409,
410,
411,
412,
413,
414,
415,
416,
417,
418,
419,
420,
421,
422,
423,
424,
425,
426,
427,
428,
429,
430,
431,
432,
433,
434,
435,
436,
437,
438,
439,
440,
441,
442,
443,
444,
445,
446,
447,
448,
449,
450,
451,
452,
453,
454,
455,
456,
457,
458,
459,
460,
461,
462,
463,
464,
465,
466,
467,
468,
469,
581,
726,
806,
939,
992,
994,
1008,
1009,
1010,
1011,
1012,
1013,
1014,
1015,
1016,
1017,
1018,
1019,
1020,
1021,
1022,
1023,
1024,
1025,
1026,
1027,
1028,
1058,
1060);

$ids=array(3248);

$start = 1401;
$end   = 1600;

$start = 1;
$end   = 10;



for ($biostor = $start; $biostor <= $end; $biostor++)
//foreach ($ids as $biostor)
{
	echo "$biostor...";
	
	// Have we done this already?	
	$pdf_url = 'https://archive.org/download/biostor-' . $biostor . '/biostor-' . $biostor . '.pdf';
	if (head($pdf_url))
	{
		echo " PDF exists (HEAD returns 200)\n";
	}
	else
	{
		// OK, need to do this
		$json = get('http://direct.biostor.org/reference/' . $biostor . '.json');

		if ($json != '')
		{
			$reference = json_decode($json);
		
			//print_r($reference);
		
			if (isset($reference->error))
			{
				echo $reference->error . "\n";
				break;
			}
		
			// Do we have this article PDF already?
		
			$article_pdf_filename = dirname(__FILE__) . '/' . $biostor . '.pdf';
			if (file_exists($article_pdf_filename))
			//if (0)
			{
				echo "Have PDF $article_pdf_filename\n";
			}
			else
			{
				// create article PDF
		
				$pdf_filename = $reference->ia->FileNamePrefix . '.pdf';
	
				$cache_dir =  dirname(__FILE__) . "/cache/" . $reference->ia->FileNamePrefix;

				// Ensure cache subfolder exists for this item
				if (!file_exists($cache_dir))
				{
					$oldumask = umask(0); 
					mkdir($cache_dir, 0777);
					umask($oldumask);
				}
		
				// Get PDF file
				if (!file_exists($cache_dir . '/' . $pdf_filename)) // don't fetch again if we don't need to
				{
					$url = 'http://www.archive.org/download/' . $reference->ia->FileNamePrefix . '/' . $pdf_filename;
		
					//echo $url . "\n";
		
					$command = "curl";
		
					if ($config['proxy_name'] != '')
					{
						$command .= " --proxy " . $config['proxy_name'] . ":" . $config['proxy_port'];
					}
					$command .= " --location " . $url . " > " . $cache_dir . '/' . $pdf_filename;
					echo $command . "\n";
					system ($command);
				}	

				// Extract each page from PDF (may be a discontinuous range if there are plates)
				foreach ($reference->ia->pages as $page)
				{
					$command = 'gs -sDEVICE=pdfwrite -dNOPAUSE -dBATCH -dSAFER '
						. ' -dFirstPage=' . $page . ' -dLastPage=' . $page
						. ' -sOutputFile=\'' . $tmpdir . '/' . $page . '.pdf' . '\' \'' .  $cache_dir . '/' . $pdf_filename . '\'';

					echo $command . "\n";

					system($command);
				}

				// Combine individual pages
				$command = "gs \\\n"
					. " -o " . $article_pdf_filename . " \\\n"
					. "-sDEVICE=pdfwrite -dNOPAUSE -dBATCH -dSAFER \\\n";
	
				$n = count($reference->ia->pages);
				for ($i = 0; $i < $n; $i++)
				{
					$command .= $tmpdir . '/' . $reference->ia->pages[$i] . '.pdf ';
					if ($i < $n - 1)
					{
						$command .= '\\';
					}
					$command .= "\n";
				}

				echo $command . "\n";

				system($command);

				// XMP?
				// Mendeley doesn't seem to recognise XMP
				if (0)
				{
					pdf_add_xmp ($reference, $article_pdf_filename);
				}
			
			}
		
			// Have PDF, now do something with it...
			if(1)
			{
				$identifier = 'biostor-' . $biostor;
		
				// upload to IA
				$headers = array();
			
				$headers[] = '"x-archive-auto-make-bucket:1"';
				$headers[] = '"x-archive-ignore-preexisting-bucket:1"';
				$headers[] = '"x-archive-interactive-priority:1"';
			
				// collection
				$headers[] = '"x-archive-meta01-collection:biostor"';

				// metadata
				$headers[] = '"x-archive-meta-sponsor:BioStor"';
				$headers[] = '"x-archive-meta-mediatype:texts"'; 
			
				if (isset($reference->title))
				{
					$headers[] = '"x-archive-meta-title:' . addcslashes($reference->title, '"') . '"';
				}
				if (isset($reference->publication_outlet))
				{
					$headers[] = '"x-archive-meta-journal:' . addcslashes($reference->publication_outlet, '"') . '"';
				}
				if (isset($reference->volume))
				{
					$headers[] = '"x-archive-meta-volume:' . addcslashes($reference->volume, '"') . '"';
				}
				if (isset($reference->pages))
				{
					$headers[] = '"x-archive-meta-pages:' . addcslashes($reference->pages, '"') . '"';
				}
				if (isset($reference->year))
				{
					$headers[] = '"x-archive-meta-year:' . addcslashes($reference->year, '"') . '"';
					$headers[] = '"x-archive-meta-date:' . addcslashes($reference->year, '"') . '"';
				}

				if (isset($reference->authors))
				{
					for ($i = 0; $i < count($reference->authors); $i++)
					{
						$author = $reference->authors[$i]->forename . ' ' . $reference->authors[$i]->surname;
						$headers[] = '"x-archive-meta' . str_pad(($i+1), 2, 0, STR_PAD_LEFT) . '-creator:' . addcslashes($author, '"') . '"';
					}
				}
			
				// licensing
				$headers[] = '"x-archive-meta-licenseurl:http://creativecommons.org/licenses/by-nc/3.0/"';

				// authorisation
				$headers[] = '"authorization: LOW ' . $config['s3_access_key']. ':' . $config['s3_secret_key'] . '"';

				$headers[] = '"x-archive-meta-identifier:' . $identifier . '"';

			
				$url = 'http://s3.us.archive.org/' . $identifier . '/' . $identifier . '.pdf';
			
				print_r($headers);
				echo "$url\n";
			
				$command = 'curl --location';
				$command .= ' --header ' . join(' --header ', $headers);
				$command .= ' --upload-file ' . $article_pdf_filename;
				$command .= ' http://s3.us.archive.org/' . $identifier . '/' . $identifier . '.pdf';

				echo $command . "\n";

				system ($command);
			

				//system ($command);
			
			}	
		}		
	}
}




?>