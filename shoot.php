<?php

/*
 *  Script to mirror these sites and mirror as well their pdf they propose. 
 *  no cURL used.
 *  DOMDocument used for node (id) traversing
 */
set_time_limit(0);

$URL_stevenhills = 'http://stevenhills.mu/FixturesForToday.aspx';
$URL_playonlineltd_dailyOddsResults = 'https://sites.google.com/site/dailyoddsresults/';
$URL_playonlineltd_fixedOdds = 'http://playonlineltd.com/fixedodds.php';
$URL_supertote = 'http://www.supertote.mu/fixed-odds.php';
$URL_totelepep = 'http://www.totelepep.mu/index.php';


$folder = createFolder();

//STEVENHILLS
$content = getWebsite($URL_stevenhills);
createFile($folder, 'Stevenhills', $content);

//PLAYONLINE FIXED ODDS
$content = getWebsite($URL_playonlineltd_fixedOdds);
createFile($folder, 'playOnline_fixedOdds', $content);

//SUPERTOTE
$content = getWebsite($URL_supertote);
createFile($folder, 'Supertote', $content);
//traverse document and grad pdfs
$doc = new DOMDocument();
$doc->loadHTML($content);
$elem = $doc->getElementById('Content');

// loop through all childNodes, getting html
$children = $elem->childNodes;
mkdir($folder . "/supertote_fixed_odds"); //create directory for pdfs
foreach ($children as $child)
{
    $tmp_doc = new DOMDocument();
    $tmp_doc->appendChild($tmp_doc->importNode($child, true));

    $links = $tmp_doc->getElementsByTagName('a');
    foreach ($links as $link) //each PDF file
    {
        $pdfFile = $link->getAttribute('href');
        $file = "http://www.supertote.mu" . $pdfFile;

        $path_parts = pathinfo($pdfFile);
        $extension = @$path_parts['extension'];

        if ($extension == "pdf") //downloads only PDF
        {
            //need to create a temporary pdf before file_put_contents
            $fp = fopen($folder . "/supertote_fixed_odds/" . basename($pdfFile), 'w');
            fwrite($fp, "");
            fclose($fp);

            @file_put_contents($folder . "/supertote_fixed_odds/" . basename($pdfFile), file_get_contents($file));
        }
    }
}

//PLAYONLINE daily odd results
$content = getWebsite($URL_playonlineltd_dailyOddsResults);
createFile($folder, 'playOnline_dailyOddsResults', $content);
//traverse document and grad pdfs
$doc = new DOMDocument();
@$doc->loadHTML($content);
$elem = $doc->getElementById('filecabinet-body');

// loop through all childNodes, getting html       
$children = $elem->childNodes;
@mkdir($folder . "/playonline_fixed_odds"); //create directory for pdfs
foreach ($children as $child)
{
    $tmp_doc = new DOMDocument();
    $tmp_doc->appendChild($tmp_doc->importNode($child, true));

    $links = $tmp_doc->getElementsByTagName('td');
    foreach ($links as $link) //each PDF file
    {
        $split = explode("View", $link->nodeValue);
        $path_parts = pathinfo($split[0]);
        $extension = @$path_parts['extension'];

        if ($extension == "xls") //downloads only .xls
        {
            $excelFile = $split[0];
            $file = "http://sites.google.com/site/dailyoddsresults/home/" . $excelFile;

            //need to create a temporary pdf before file_put_contents
            $fp = fopen($folder . "/playonline_fixed_odds/" . basename($excelFile), 'w');
            fwrite($fp, "");
            fclose($fp);

            file_put_contents($folder . "/playonline_fixed_odds/" . basename($excelFile), file_get_contents(str_replace(" ", "%20", $file)));
        }
    }
}

//TOTELEPEP
$postdata = http_build_query(
        array(
            'menu' => 'footOdds', //to access football page bypassing the menu
            'anchor' => null
        )
);

$opts = array('http' =>
    array(
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    )
); //bypassing the menu access ;) reverse engineering FTW :D

$context = stream_context_create($opts);
$content = file_get_contents($URL_totelepep, false, $context);

createFile($folder, 'Totelepep', $content);


//COMPLETED MESSAGE
echo "<h1 style='font-family: Calibri'>Completed : <a href='index.php'> Back to Directory </a></h1>";

//FUNCTIONS
function getWebsite($url)
{
    $file = file_get_contents($url);
    return $file;
}

function createFolder()
{
    $filename = date('D_d-m-y@H-i');
    if (!is_dir($filename))
    {
        mkdir($filename);
    }
    return $filename;
}

function createFile($folder, $filename, $content)
{
    $fp = fopen($folder . "/" . $filename . ".html", 'w');
    fwrite($fp, $content);
    fclose($fp);
}
