SELECT 
  g."PVE_NAAM" naam,
  gme_vlag vlag,
  gme_lat lat,
  gme_lng lng,
  count,
  "LET",
  "DOD",
  "UMS"
FROM gemeentes g
JOIN gemeentes_stats gs ON 
	gs."PVE_NAAM" = g."PVE_NAAM"
	AND gs."GME_NAAM" = g."GME_NAAM"
WHERE 
  POINT(gme_lat, gme_lng) <@ box(point(:sw_lat, :sw_lng), point(:ne_lat, :ne_lng)) 