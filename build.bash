#!/bin/sh

rm -f innokassa.ocmod.zip
cp -R src upload
zip -rm innokassa.ocmod.zip upload
