#!/usr/bin

if [ -d "./sites/default" ]
then
	echo "sites default directory  exists!"
else
	mkdir "./sites/default"
  cp -r ./sites-cp/* ./sites/.
fi

#if [ -d "./sites/default/config/sync" ]
#then
#  echo "sites default config directory  exists!"
#else
#  mkdir "./sites/default/config"
#  mkdir "./sites/default/config/sync"
#  cp ./sites-cp/default/config/sync/* ./sites/default/config/sync/.
#fi
