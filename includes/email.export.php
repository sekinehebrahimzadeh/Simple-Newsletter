<?php

header("Content-Type: application/xls");
header("Content-Disposition: attachment; filename=nlsm-".date('Ymd').".xls");
header("Pragma: no-cache");
header("Expires: 0");

include('../../../../wp-load.php');
global $wpdb;
$results	= $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wbnl ORDER BY id");

	?>
<table border="1">
   <tr style="background: #bbb;">
      <td width="5%">ID</td>
      <td width="14%">Name</td>
      <td width="14%">Email</td>
      <td width="14%">Phone</td>
   </tr>
<?php
foreach($results as $key => $result) {
   $key++;
   $tbl = "<tr>
           <td >$key</td>
           <td >".esc_html($result->name)."</td>
           <td >".esc_html($result->email)."</td>
           <td >".esc_html($result->phone)."</td>
       </tr>";
   echo $tbl;
}//end foreach
?>
</table>
