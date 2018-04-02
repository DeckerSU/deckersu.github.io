<?php

/* 
    (c) Decker, 2018
*/

define('SATOSHIDEN', "100000000");

function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       


    $data["result"] = curl_exec($ch);
    $data["http_code"] = curl_getinfo($ch)["http_code"];
    curl_close($ch);

    return $data;
}


echo '<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/css/dataTables.bootstrap.min.css" rel="stylesheet"/>
<div class="container">
   <!-- (c) Decker, 2018 -->
   <h1>VOTE2018 Results</h1>
   <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
      <thead>
         <tr>
            <th>Region</th>
            <th>Address</th>
            <th>Name</th>
            <th>Balance (Explorer)</th>
            <th>Balance (Snapshot)</th>
         </tr>
      </thead>
      <tbody>';


// read candidates list from csv
if ($file = fopen("nn_candidates_last.csv", "r")) {
    $line_index = 0;
    while(!feof($file)) {
    $line = fgets($file);
    if (($line) && ($line_index!=0)) {
        $candidate = explode(";", $line);    
        $json_data[$candidate[2]]["label"] = trim($candidate[0]) . " (".trim($candidate[1]).")";
        $json_data[$candidate[2]]["url"] = trim($candidate[3]);
        
    }
    $line_index++;
    }
}
file_put_contents("labels.json",json_encode($json_data));

// read snapshot data
if ($file = fopen("!results.txt", "r")) {
    $line_index = 0;
    while(!feof($file)) {
    $line = fgets($file);
    if (($line) && ($line_index>5)) {
        $candidate = explode(" ", trim($line));    
        $snapshot_data[$candidate[2]] = trim($candidate[0]);
    }
    $line_index++;
    }
}

$json = file_get_contents("labels.json");
$json_data = json_decode($json);

$sum = 0;

foreach ($json_data as $address => $candidate) {
    //$explorer_data = file_get_contents("https://vote2.explorer.supernet.org/address/".$address);
    
    /*
    <table class="table table-bordered table-striped summary-table"><thead><tr><th>Total Sent (VOTE)</th><th>Total Received (VOTE)</th><th>Balance (VOTE)</th></tr></thead><tbody>            <tr><td>0.00000000</td><td>492905570.00000000</td><td>492905570.00000000</td></tr></tbody></table>*/
    
    //preg_match("#<tr><td>([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)<\/td><td>([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)<\/td><td>([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)<\/td><\/tr>#",$explorer_data,$matches);
    //var_dump($matches);
    //$balance = trim($matches[5]);

    $res = file_get_contents_curl("http://172.17.112.37:3002/insight-api-komodo/addr/".$address."/balance");
    $balance = 0;
    $balance_snapshot = 0;
    if ($res["result"] != "0") { 
	$balance = bcdiv($res["result"], SATOSHIDEN, 8);
    }	
    preg_match("#\((.*)\)#",$candidate->label,$matches);
    $region = $matches[1];

    if ($address != "RBurntQ2utjfVPUSp1NgcfkmokyPzZQRi4") {
    if (array_key_exists($address,$snapshot_data)) {
	$balance_snapshot = $snapshot_data[$address];
    }
    echo '
         <tr>
	    <td>'.$region.'</td>
            <td><a href="https://vote2.explorer.supernet.org/address/'.$address.'" target="_blank">'.$address.'</a></td>
            <td><a href="'.$candidate->url.'" target="_blank">'.$candidate->label.'</a></td>
            <td>'.$balance.'</td>
            <td>'.$balance_snapshot.'</td>
         </tr>
';

$sum += $balance;
}

}

echo '      </tbody>
      <tfoot>
            <tr>
                <th colspan="3" style="text-align:right">Total:</th>
                <th></th>
                <th></th>
            </tr>
      </tfoot>	
   </table>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/dataTables.bootstrap.min.js"></script>
';

?>

<script>
$(document).ready(function() {
    $('#example').DataTable( {
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
 
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
 
            // Total over all pages
            total = api
                .column( 3 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );

            total4 = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );

 
            // Total over this page
            pageTotal = api
                .column( 3, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );

            pageTotal4 = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
 
            // Update footer
            $( api.column( 3 ).footer() ).html(
                ''+pageTotal +' VOTE2018\n'+ total +' VOTE2018'
            );

            $( api.column( 4 ).footer() ).html(
                ''+pageTotal4 +' VOTE2018\n'+ total4 +' VOTE2018'
            );

        }
    } );
} );
</script>

