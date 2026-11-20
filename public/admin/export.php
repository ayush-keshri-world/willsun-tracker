<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/src/helpers.php'; require_admin();
$type=$_GET['type']??'';
if(!in_array($type,['visits','orders'],true)){http_response_code(400);exit('Choose visits or orders.');}
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$type.'-'.date('Y-m-d').'.csv"');
$out=fopen('php://output','w');
if($type==='visits'){
    fputcsv($out,['Visit ID','Party','MR','Visited at','Latitude','Longitude','Distance metres','Location tag','Status','Remark','Photo available']);
    $rows=db()->query('SELECT v.*,p.name AS party_name,u.name AS mr_name FROM visits v JOIN parties p ON p.id=v.party_id JOIN users u ON u.id=v.mr_id ORDER BY v.visited_at DESC');
    foreach($rows as $row){fputcsv($out,[$row['id'],$row['party_name'],$row['mr_name'],$row['visited_at'],$row['latitude'],$row['longitude'],$row['distance_m'],$row['location_tag'],$row['status'],$row['remark'],$row['photo_path']?'Yes':'No']);}
}else{
    fputcsv($out,['Order ID','Party','MR','Order date','Product','Quantity','Amount']);
    $rows=db()->query('SELECT o.*,p.name AS party_name,u.name AS mr_name FROM orders o JOIN parties p ON p.id=o.party_id JOIN users u ON u.id=o.mr_id ORDER BY o.order_date DESC');
    foreach($rows as $row){fputcsv($out,[$row['id'],$row['party_name'],$row['mr_name'],$row['order_date'],$row['product'],$row['quantity'],$row['amount']]);}
}
fclose($out);
