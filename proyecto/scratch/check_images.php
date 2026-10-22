<?php
$sz1 = getimagesize('practicas antigrabiti/public/assets/image1.png');
$sz2 = getimagesize('practicas antigrabiti/public/assets/image2.png');
echo 'image1: ' . json_encode($sz1) . "\n";
echo 'image2: ' . json_encode($sz2) . "\n";
