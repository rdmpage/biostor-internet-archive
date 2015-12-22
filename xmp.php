<?php

//----------------------------------------------------------------------------------------
/**
 * @brief Inject XMP metadata into PDF
 *
 * We inject XMP metadata using Exiftools
 *
 * @param reference Reference 
 * @param pdf_filename Full path of PDF file to process
 * @param tags Tags to add to PDF
 *
 */
function pdf_add_xmp ($reference, $pdf_filename)
{	
	// URL
	
	if (isset($reference->identifiers))
	{
		if (isset($reference->identifiers->biostor))
		{
			$command = "exiftool" .  " -XMP:URL=" . escapeshellarg('http://biostor.org/reference/' . $reference->identifiers->biostor) . " " . $pdf_filename;	
			system($command);
		}
	}
	
	// Mendeley will overwrite XMP-derived metadata with CrossRef metadata if we include this
	if (isset($reference->doi))
	{
		$command = "exiftool" .  " -XMP:DOI=" . escapeshellarg($reference->doi) . " " . $pdf_filename;
		system($command);
	}
	
	// Title and authors
	$command = "exiftool" .  " -XMP:Title=" . escapeshellarg($reference->title) . " " . $pdf_filename;
	system($command);
	
	foreach ($reference->authors as $a)
	{
		//$command = "exiftool" .  " -XMP:Creator+=" . escapeshellarg($a) . " " . $pdf_filename;
		//system($command);
	}
	
	// Article
	if ($reference->type == 'Journal Article')
	{
		$command = "exiftool" .  " -XMP:AggregationType=journal " . $pdf_filename;
		system($command);
		$command = "exiftool" .  " -XMP:PublicationName=" . escapeshellarg($reference->publication_outlet) . " " . $pdf_filename;
		system($command);
		
		if (isset($reference->issn))
		{
			$command = "exiftool" .  " -XMP:ISSN=" . escapeshellarg($reference->issn) . " " . $pdf_filename;
			system($command);
		}
				
		$command = "exiftool" .  " -XMP:Volume=" . escapeshellarg($reference->volume) . " " . $pdf_filename;
		system($command);
		if (isset($reference->issue))
		{
			$command = "exiftool" .  " -XMP:Number=" . escapeshellarg($reference->issue) . " " . $pdf_filename;
			system($command);
		}
		
		/*
		$command = "exiftool" .  " -XMP:StartingPage=" . escapeshellarg($reference->spage) . " " . $pdf_filename;
		system($command);
		if (isset($reference->epage))
		{
			$command = "exiftool" .  " -XMP:EndingPage=" . escapeshellarg($reference->epage) . " " . $pdf_filename;
			system($command);
			$command = "exiftool" .  " -XMP:PageRange+=" . escapeshellarg($reference->spage. '-' . $reference->epage) . " " . $pdf_filename;
			system($command);
		}
		*/
	}
	
	if (isset($reference->year))
	{
		$command = "exiftool" .  " -XMP:CoverDate=" . escapeshellarg(str_replace("-", ":", $reference->year)) . " " . $pdf_filename;
		system($command);
	}
	
	// cleanup
	if (file_exists($pdf_filename . '_original'))
	{
		unlink($pdf_filename . '_original');
	}
	
}	

?>