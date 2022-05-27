SELECT 
	latlng[0] lat, 
	latlng[1] lng,
	COUNT(*) as count,
	SUM(CASE WHEN "AP3_CODE"='LET' THEN 1 ELSE 0 END) AS "LET",
	SUM(CASE WHEN "AP3_CODE"='DOD' THEN 1 ELSE 0 END) AS "DOD",
	SUM(CASE WHEN "AP3_CODE"='UMS' THEN 1 ELSE 0 END) AS "UMS"
FROM ongevallen o
WHERE 
	-- latlng <@ box(point(50.8510411296595, 1.5325927734375002), point(53.608803292930894, 9.970092773437502)) 
  latlng <@ box(point(:sw_lat, :sw_lng), point(:ne_lat, :ne_lng)) 
GROUP BY(lat, lng)
LIMIT 10000

