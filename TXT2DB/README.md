1. sort -u -t, -k1,1 ../01-01-2011_31-12-2020/Ongevallengegevens/ongevallen.txt | php ./clean_ongevallen.php|tee data/ongevallen.csv
2. cp ../01-01-2011_31-12-2020/ReferentiebestandenOngevallen/aardongevallen.txt data/aardongevallen.csv
3. cp ../01-01-2011_31-12-2020/ReferentiebestandenOngevallen/aflopen3.txt data/aflopen.csv
4. cp ../01-01-2011_31-12-2020/ReferentiebestandenOngevallen/wegsituaties.txt data/wegsituaties.csv


Coordinaten gemeentes download CSV via https://query.wikidata.org/:
`
SELECT ?gmeLabel ?provLabel ?lat ?lng
WHERE {
  ?gme wdt:P31 wd:Q2039348; wdt:P625 ?loc; wdt:P131 ?prov .
  ?prov wdt:P31 wd:Q134390 .
  bind( replace( str(?loc), "^[^0-9\\.-]*([-]?[0-9\\.]+) .*$", "$1" ) as ?lng )
  bind( replace( str(?loc), "^.* ([-]?[0-9\\.]+)[^0-9\\.]*$", "$1" ) as ?lat )
  
  SERVICE wikibase:label { bd:serviceParam wikibase:language "nl" }
}

opgezocht via Geonames:
INSERT INTO provincies VALUES('DR', 'Drenthe', 52.83333, 6.58333); 
INSERT INTO provincies VALUES('FL', 'Flevoland', 52.53333, 5.66667);
INSERT INTO provincies VALUES('FR', 'Friesland', 53.16667, 5.83333);
INSERT INTO provincies VALUES('GL', 'Gelderland', 52, 5.83333);
INSERT INTO provincies VALUES('GR', 'Groningen', 53.25, 6.75);
INSERT INTO provincies VALUES('LB', 'Limburg', 51.25, 6);
INSERT INTO provincies VALUES('NB', 'Noord-Brabant', 51.66667, 5);
INSERT INTO provincies VALUES('NH', 'Noord-Holland', 52.58333, 4.91667);
INSERT INTO provincies VALUES('OV', 'Overijssel', 52.41667, 6.5);
INSERT INTO provincies VALUES('UT', 'Utrecht', 52, 5.25);
INSERT INTO provincies VALUES('ZH', 'Zuid-Holland', 52, 4.66667);
INSERT INTO provincies VALUES('ZL', 'Zeeland', 51.41667, 3.75);

Download CSV vlaggen van gemeentes (niet alle gemeentes hebben dat):
SELECT ?gmeLabel ?flag
WHERE {
  ?gme wdt:P31 wd:Q2039348; wdt:P131 ?prov; wdt:P41 ?flag .
  ?prov wdt:P31 wd:Q134390 .
  SERVICE wikibase:label { bd:serviceParam wikibase:language "nl" }
}
Run script `download-vlaggen-per-gemeente.php`