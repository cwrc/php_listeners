Supporting tools
--

init.d script to allow microservice to automatically start at OS startup
--
sudo ln -s ./fedora_microservice /etc/init.d

To test

* sudo logrotate -fv /etc/logrotate.d/fedora_microservice_rotate
* sudo logrotate -dv /etc/logrotate.d/fedora_microservice_rotate
* vim /var/lib/logrotate.status - dates remembered by logrotate, change to test
update


log rotate script
--
* sudo ln -s ./fedora_microservice_rotate /etc/logrotate.d
* update directory of log files
