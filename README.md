# biostor-internet-archive

This code generates and stores PDFs for articles from BioStor on the Internet Archive (IA). The PDFs are extracted from PDFs of scans stored on IA, then stored individually.

## Background

IA implements a Amazon S3-like API, see http://archive.org/help/abouts3.txt for details. You can upload a PDF together with metadata to IA, and IA will then process it and generate OCR, ePub, and other file formats.

- Your S3 access keys are available here: http://archive.org/account/s3.php
- Once a PDF is uploaded it gets processed by IA. To check on the status of you tasks go to http://archive.org/catalog.php?justme=1

## Creating PDF

Given a BioStor article id, the code fetches metadata about that article, including the set of corresponding pages in BHL (may be discontinuous) and the IA identifier of the scanned volume that contains the article. We then fetch the PDF of the scan (which has searchable text), extract the pages, join them together in a new PDF, add XMP metadata, and then post to IA.
