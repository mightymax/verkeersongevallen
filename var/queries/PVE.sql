SELECT 
	"PVE_NAAM" naam,
  pve_vlag vlag,
  lat,
  lng,
  count,
  "LET",
  "DOD",
  "UMS"
FROM provincies
WHERE 
  1=1 OR POINT(lat, lng) <@ box(point(:sw_lat, :sw_lng), point(:ne_lat, :ne_lng)) 
