<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/xmp.php');

//----------------------------------------------------------------------------------------


$tmpdir = dirname(__FILE__) . '/tmp';

// BioStor reference numbers
$ids = array(160234);


foreach ($ids as $biostor)
{

	$json = get('http://direct.biostor.org/reference/' . $biostor . '.json');

	if ($json != '')
	{
		$reference = json_decode($json);
		
		// Do we have this article PDF already?
		
		$article_pdf_filename = dirname(__FILE__) . '/' . $biostor . '.pdf';
		if (file_exists($article_pdf_filename))
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




?>