<?php
include('phpqrcode/qrlib.php');

$url = 'https://inventario-ti.app/test'; 


QRcode::png($url, 'img/qr/test01.png',QR_ECLEVEL_H, 10);


