<?php
// حماية المجلد - منع الوصول المباشر
header('HTTP/1.1 403 Forbidden');
exit('Access Denied'); 