# Ongevallen in Nederland
Deze applicatie gebruikt de [data van Rijksswaterstaat](https://data.overheid.nl/dataset/9841-verkeersongevallen---bestand-geregistreerde-ongevallen-nederland) om ongevallen in Nederland op een kaart te tonen.

## Installatie en import data
```bash
git clone https://github.com/mightymax/verkeersongevallen
cd verkeersongevallen
docker-compose up
CID=`docker ps | grep "verkeersongevallen_app" | awk '{ print $1 }'`
docker container exec -it $CID var/download-dataset.sh 01-01-2011_31-12-2020
```

Het kan nodig zijn om je data op te schonen:
- toon lijst van plaatsnamen uit ongelukken die niet in Wikidata gevonden zijn en pas waar nodig aan:
```bash
docker container exec --tty $CID ./app import:orphins -pi
```
- Toon lijst van plaatsnamen uit de Wikidata set waar geen ongelukken van zijn en verwijder deze:
```bash
docker container exec --tty $CID ./app import:orphins -o --delete
```
