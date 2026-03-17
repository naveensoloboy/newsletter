<?php
// ─── config/config.php ───────────────────────────────────────

define('MONGO_URI',      'mongodb://admin:qutrixpass2025@cluster1-shard-00-00.duscp.mongodb.net:27017,cluster1-shard-00-01.duscp.mongodb.net:27017,cluster1-shard-00-02.duscp.mongodb.net:27017/?ssl=true&replicaSet=atlas-6iy4qu-shard-0&authSource=admin&appName=Cluster1');
define('MONGO_DB',       'newsletter_db');

define('JWT_SECRET',     'change-this-to-a-long-random-secret-key-2025');
define('JWT_EXPIRY',     86400); // 24 hours

define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'naveen9222777@gmail.com');
define('SMTP_PASSWORD',  'bzze mlve sqsw hivl');
define('FROM_EMAIL',     'newsletter@college.edu');
define('FROM_NAME',      'Newsletter Management System');

define('FRONTEND_URL',   'http://localhost:5500');
define('UPLOAD_DIR',     __DIR__ . '/../uploads/');
define('UPLOAD_URL',     'http://localhost/newsletter_php/backend/uploads/');
define('MAX_FILE_SIZE',  16 * 1024 * 1024); // 16MB
define('ALLOWED_TYPES',  ['image/jpeg','image/png','image/gif','image/webp']);

// CORS — allow frontend origin
define('CORS_ORIGIN', '*');
