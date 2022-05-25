var NL = false;
var zoomLevel = 14;
var markerClusterGroup;

var AP3_CODES = {
  DOD: "Dodelijk",
  LET: "Letsel",
  UMS: "Uitsluitend materiele schade",
  LLI: "Letsel licht",
  LZW: "Letsel zwaar",
}

var AOL_IDS = {
  3: "Dier",
  9: "Eenzijdig",
  7: "Flank",
  6: "Frontaal",
  2: "Geparkeerd voertuig",
  8: "Kop/staart",
  5: "Los voorwerp",
  0: "Onbekend",
  4: "Vast voorwerp",
  1: "Voetganger",
}

function iconCreateFunction (cluster)
{
  var count = 0;
  cluster.getAllChildMarkers().forEach(marker => {
    count += marker.ongevallen;
  });

		var c = ' marker-cluster-';
		if (count < 10) {
			c += 'small';
		} else if (count < 100) {
			c += 'medium';
		} else {
			c += 'large';
		}

		return new L.DivIcon({ html: '<div><span>' + count + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });
}



function loadMarkers(map) {

  var zoom = map.getZoom();
  var options = {}
  if (zoom <= 9) {
    var location = './ongevallen-per-provincie.json';
  } else if (zoom <= 12) {
    var location = './ongevallen-per-gemeente.json';
  } else {
    var location = './api.php';
    options = {
      method: 'POST',
      body: JSON.stringify({zoom: map.getZoom(), bounds: map.getBounds()})
    }
  }
    // if (zoom === zoomLevel) return;
  zoomLevel = zoom
  console.log(location);

  fetch(location, options)
  .then(response => response.json())
  .then(markers => {
    map.removeLayer(markerClusterGroup);
    markerClusterGroup = L.markerClusterGroup({
      iconCreateFunction: iconCreateFunction,
      chunkedLoading: true
      });
    markers.forEach(m => {
      var marker = L.marker([m.lat, m.lng],{
          icon: L.divIcon({
            html: m.count,
            className: `afloop`, 
            iconSize: [16,16]
          })
      });
      marker.ongevallen = m.count
      markerClusterGroup.addLayer(marker);
    });
    map.addLayer(markerClusterGroup);

  })
  .catch(err => {
    alert("er is een fout opgetreden bij het ophalen van de ongelukken");
  });
  
}

window.onload = function() {
  var tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    minZoom: 8,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, productie <a href="https://twitter.com/markuitheiloo">@markuitheiloo</a>'
  });
  var latlng = L.latLng(52.6011, 4.7019);
  var map = L.map('map', {center: latlng, zoom: zoomLevel, layers: [tiles]});

  markerClusterGroup = L.markerClusterGroup({
    iconCreateFunction: iconCreateFunction,
    chunkedLoading: true
  });


  var isLoaded = false;
  map.on('moveend', function(e) {
    loadMarkers(map)
  });
  tiles.on('load', function(e) {
    if (isLoaded) return;
    isLoaded = true;
    loadMarkers(map)
  });
  

  return;

  if (!NL) {
    var legend = L.control({ position: "bottomleft" });
    legend.onAdd = function(map) {
      var div = L.DomUtil.create("div", "legend");
      div.innerHTML = `<h4>Aard ongeval:</h4>
      <p><span class="afloop afloop-dodelijk"></span> dodelijk</p>
      <p><span class="afloop afloop-letsel"></span> letsel</p>
      <p><span class="afloop afloop-mixed"></span> materiele schade</p>
      `;
      return div;
    };
    legend.addTo(map);
  }

  fetch(NL ? 'ongevallen-NL.json' :'ongevallen-Heiloo.json')
    .then(response => response.json())
    .then(ongevallen => {
      for (var locId in ongevallen) {
        ongeval = ongevallen[locId];
        var marker = L.marker(
          [NL ? ongeval[0] : ongeval.lat, NL ? ongeval[1] : ongeval.lon],
          {
            icon: L.divIcon({
              html: NL ? ongeval[2] : ongeval.c,
              className: `afloop afloop-${afloop}`, 
              iconSize: [16,16]
            })
          }
        );
        if (!NL) {
          var html = `<p><b>Afloop:</b>`;
          var afloop = 'mixed';
          if (typeof ongeval.AP3_CODE.DOD != 'undefined') afloop = 'dodelijk'
          else if (typeof ongeval.AP3_CODE.LET != 'undefined') afloop = 'letsel'
          else if (typeof ongeval.AP3_CODE.LLI != 'undefined') afloop = 'letsel'
          else if (typeof ongeval.AP3_CODE.LZW != 'undefined') afloop = 'letsel'

          for (var AP3_CODE in ongeval.AP3_CODE) {
            html += `<br> • ${AP3_CODES[AP3_CODE]}: <i>${ongeval.AP3_CODE[AP3_CODE]}</i>`;
          }
          html += "</p>"
          html += `<p><b>Aard:</b>`;
          for (var AOL_ID in ongeval.AOL_ID) {
            html += `<br> • ${AOL_IDS[AOL_ID]}: <i>${ongeval.AOL_ID[AOL_ID]}</i>`;
          }
          html += "</p>"
          html += `<p><b>Per jaar:</b> `;
          for (var Y in ongeval.Y) {
            html += `${Y}: <i>${ongeval.Y[Y]}</i>; `;
          }
          html += "</p>"
          marker.bindPopup(html);
        }
        marker.ongevallen = NL ? ongeval[2] : ongeval.c
        markerClusterGroup.addLayer(marker);
      }
      map.addLayer(markerClusterGroup);
  })
}
