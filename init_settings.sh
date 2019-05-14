#!/usr/bin

if [ -d "./sites/default" ]
then
	echo "sites default directory  exists!"
else
	mkdir "./sites/default"
fi

cp ./sites-cp/default/*settings.php ./sites/default/.
