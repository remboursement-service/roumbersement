<?php
// حماية مجلد السجلات - منع الوصول المباشر
header('HTTP/1.1 403 Forbidden');
exit('Access Denied - Security Logs Protected'); 