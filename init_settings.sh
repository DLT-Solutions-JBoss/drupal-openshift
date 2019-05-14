if [ -d "/opt/app-root/src/sites/default" ]
then
	echo "sites default directory  exists!"
else
	mkdir "/opt/app-root/src/sites/default"
fi

cp /opt/app-root/src/sites-cp/default/*settings.php /opt/app-root/src/sites/default/.
