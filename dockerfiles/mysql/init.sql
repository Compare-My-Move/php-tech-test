-- Grant root remote access (use with caution in production!)
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'root_password' WITH GRANT OPTION;
FLUSH PRIVILEGES;
