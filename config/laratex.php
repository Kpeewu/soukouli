<?php

return [
	// bin path to your pdflatex installation | use 'which pdflatex' to find the path
	// on your system. Defaults to the Linux/Docker path; override with LATEX_BIN_PATH
	// in .env (e.g. /Library/TeX/texbin/pdflatex on macOS) for local development.
	'binPath' => env('LATEX_BIN_PATH', '/usr/bin/pdflatex'),

	// bin path to your bibtex installation | use 'which bibtex' to find the path
	// on your system. Override with LATEX_BIBTEX_PATH in .env if needed.
	'bibTexPath' => env('LATEX_BIBTEX_PATH', '/usr/bin/bibtex'),

	// Folder in your storage folder where you would like to store the temp files created by LaraTeX
	'tempPath' => 'app/',

	// boolean to define if log, aux and tex files should be deleted after generating PDF
	'teardown' => true,
];
