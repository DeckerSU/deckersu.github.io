<?php

/* (c) Decker, 2018 */

define('RPCUSER',"");
define('RPCPASSWORD',"");
define('RPCPORT',15488);
define('SATOSHIDEN', "100000000");
define('LASTBLOCK',6700);

// > curl --user myusername --data-binary '{"jsonrpc": "1.0", "id":"curltest", "method": "getblock", "params": [12800] }' -H 'content-type: text/plain;' http://127.0.0.1:8232/                                                                                                  

function daemon_request($daemon_ip, $rpcport, $rpcuser, $rpcpassword, $method, $params)
{
    
    $ch = curl_init();
    $url = $daemon_ip.":".RPCPORT;
    // var_dump($url);
    
    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);   
    curl_setopt($ch, CURLOPT_USERPWD, $rpcuser . ":" . $rpcpassword);  
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $payload = json_encode( array( "method"=> $method, "params" => $params ) );
    // var_dump($payload);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));


    $data["result"] = curl_exec($ch);
    $data["http_code"] = curl_getinfo($ch)["http_code"];
    curl_close($ch);
    // var_dump($data);
    return $data;

}

if(php_sapi_name() != "cli") return;

$json = file_get_contents("labels.json");
$json_data = json_decode($json,true);
$candidates_addresses = array_keys($json_data);

// https://stackoverflow.com/questions/31888566/bootstrap-how-to-sort-table-columns

echo '<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/css/dataTables.bootstrap.min.css" rel="stylesheet"/>
<div class="container">
   <!-- (c) Decker, 2018 -->
   <h1>Agama Usage on NN Elections 2018</h1>
   <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
      <thead>
         <tr>
            <th>Block #</th>
            <th>Input Addresses</th>
            <th>TXID</th>
            <th>Field</th>
            <th>Amount (VOTE)</th>
         </tr>
      </thead>
      <tbody>';
$unique_addresses = Array();

$blocks_res = daemon_request("127.0.0.1", RPCPORT, RPCUSER, RPCPASSWORD, "getblockcount", Array());
if ($blocks_res["http_code"] == 200) {
    $blocks_json_object = json_decode($blocks_res["result"]);
    $blocks = $blocks_json_object->result;
    
    //for ($block_height = 6000; $block_height <= 6100; $block_height++) {
    for ($block_height = 0; $block_height <= LASTBLOCK; $block_height++) {
        if ($block_height % 1000 == 0) fwrite(STDERR, "Parsing block #$block_height\n");
        $res = daemon_request("127.0.0.1", RPCPORT, RPCUSER, RPCPASSWORD, "getblock", Array("".$block_height));
        if ($res["http_code"] == 200) {
            $json_object = json_decode($res["result"]);
            $hash = $json_object->result->hash;
            $height = $json_object->result->height;
            $txs = $json_object->result->tx;

            foreach ($txs as $tx) {
                //echo "\n [" . $tx . "]\n"; // tx hash
                $tx_res = daemon_request("127.0.0.1", RPCPORT, RPCUSER, RPCPASSWORD, "getrawtransaction", Array($tx, 1));
                if ($tx_res["http_code"] == 200) {
                            //var_dump($tx_res);
                            $tx_json_object = json_decode($tx_res["result"]);
                            $vouts = $tx_json_object->result->vout;
                            $vout_sum = 0;
                    
                            foreach ($vouts as $vout) {
                        //if ($vout->value == 0.00001) {
                            //var_dump($vout);
                            
                            
                            if (property_exists($vout->scriptPubKey,"addresses")) {
                                //var_dump($vout->scriptPubKey->addresses);
                                
                                // assume we have only one address in one vout (!)
                                if (in_array($vout->scriptPubKey->addresses[0],$candidates_addresses)) $vout_sum += $vout->value;
                            }
                            
                            $hex = pack("H*",$vout->scriptPubKey->hex);
                            if (Ord($hex[0]) == 0x6a) {
                                    $field = substr($hex,2,Ord($hex[1]));
                                    if (strpos($field,"ne2k18") !== false) {
                                    $vins = $tx_json_object->result->vin;
                                    $addresses = Array();
                                    foreach ($vins as $vin) {
                                        //var_dump($vin->txid,$vin->vout);
                                        $vin_tx_res = daemon_request("127.0.0.1", RPCPORT, RPCUSER, RPCPASSWORD, "getrawtransaction", Array($vin->txid, 1));
                                        if ($vin_tx_res["http_code"] == 200) {
                                            $vin_tx_json_object = json_decode($vin_tx_res["result"]);
                                            //var_dump($vin_tx_json_object->result->vout[$vin->vout]->scriptPubKey->addresses);
                                            $addresses = array_unique(array_merge($addresses , ($vin_tx_json_object->result->vout[$vin->vout]->scriptPubKey->addresses))); 
                                            $unique_addresses = array_unique(array_merge($unique_addresses, $addresses));
                                            // $vout_sum -= $vout->value; // don't sum OP_RETURN Agama ID TX
                                        }
                                    }
                                    //var_dump($addresses);
                                    
                                        
                                    echo '
                     <tr>
                        <td><a href="https://vote2.explorer.supernet.org/block/'.$hash.'" target="_blank">'.sprintf("%6d",$height).'</a></td>
                        <td>'.implode("<br />",$addresses).'</td>
                        <td><a href="https://vote2.explorer.supernet.org/tx/'.$tx.'" target="_blank">'.substr($tx,0,10)."...".substr($tx,-10).'</a></td>
                    <td>'.$field.'</td>
                    <td>'.$vout_sum.'</td>
                     </tr>
            ';
                            
                                    }
                            //}
                            
                            }
                        //}
                        }
                }
            }



        }
        //die;
    }

    
}
echo '      </tbody>
      <tfoot>
            <tr>
                <th colspan="4" style="text-align:right">Total:</th>
                <th></th>
            </tr>
      </tfoot>	
   </table>';
echo "<b>Unique Input Addresses: </b>" . count($unique_addresses) . "<br />";
echo '
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/dataTables.bootstrap.min.js"></script>
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
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );

            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );

            // Update footer
            $( api.column( 4 ).footer() ).html(
                ''+pageTotal +' VOTE2018\n'+ total +' VOTE2018'
            );

        }
    } );
} );
</script>
