SELECT 
	"PVE_NAAM" naam,
  "PVE_VLAG" vlag,
  lat,
  lng,
  count,
  "LET",
  "DOD",
  "UMS"
FROM provincies
WHERE 
  POINT(lat, lng) <@ box(point(:sw_lat, :sw_lng), point(:ne_lat, :ne_lng)) 
