<?php
// For hosts where the document root cannot point to public/: forward to the real entry point.
header('Location: public/index.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 302);
