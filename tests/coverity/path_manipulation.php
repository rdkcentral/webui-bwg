<?php
/*
 * Intentionally unsafe static-analysis fixture. Never deploy or execute.
 * Expected finding: PATH_MANIPULATION from $_POST['mode'] to fopen().
 */
$mode = $_POST['mode'];
$fh = fopen("/tmp/parental_reports_".$mode."_Today.txt", "w");
if ($fh === false) {
    die("Unable to open path manipulation test file.");
}
fwrite($fh, "Coverity path manipulation test\n");
fclose($fh);
