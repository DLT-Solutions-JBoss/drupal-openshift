#!/usr/bin

if [ -d "./sites/default" ]
then
	echo "sites default directory  exists!"
else
	mkdir "./sites/default"
fi

cp ./sites-cp/default/*settings.php ./sites/default/.

if [ -d "./sites/default/config/sync" ]
then
	echo "sites default config directory  exists!"
else
	mkdir "./sites/default/config"
  mkdir "./sites/default/config/sync"
fi

cp ./sites-cp/default/config/sync/* ./sites/default/config/sync/.
