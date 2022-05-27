#!/usr/bin/env bash
cd /tmp

test -z $1 && {
  echo "usage: "$0" dataset-id" 1>&2
  exit 2
}
ds=$1

test -d $ds && {
  echo -n "removing existing data:"
  rm -rf $ds
  test -f $ds.zip && rm $ds.zip
  echo "done"
}
curl -O https://www.rijkswaterstaat.nl/apps/geoservices/geodata/dmc/bron/$ds.zip
test $? -eq 0 || exit
unzip $ds.zip \
  $ds/Netwerkgegevens/puntlocaties.txt \
  $ds/Ongevallengegevens/ongevallen.txt
rm $ds.zip
ls -al /tmp
echo "importeer puntlocaties:"
/app/app import:puntlocaties /tmp/$ds/Netwerkgegevens/puntlocaties.txt -vvv
/app/app import:gemeentes  -vvv
echo "importeer ongevallen:"
/app/app import:ongevallen /tmp/$ds/Ongevallengegevens/ongevallen.txt -vvv
rm -rf $ds.*
/app/app import:orphins -oi
/app/app import:orphins -g --delete >/dev/null 2>/dev/null
