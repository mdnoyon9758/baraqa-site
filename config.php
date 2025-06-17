<?php
// bs/config.php

// The key MUST be 32 bytes long for AES-256-CBC.
define('ENCRYPTION_KEY', 'a_super_secret_32_byte_long_key_'); 

// The IV MUST be 16 bytes long for AES-256-CBC.
define('ENCRYPTION_IV', 'a-secret-16-byte'); // Changed to exactly 16 bytes
?>