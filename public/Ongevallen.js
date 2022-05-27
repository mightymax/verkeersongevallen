class Ongevallen 
{
  config = {
    nodeId: 'map',
    mode: 'PVE',
    center: L.latLng(52.25, 5.75), //Nederland
    // center: L.latLng(52.6011, 4.7019), //Heiloo
    minZoom: 8,
    maxZoom: 18,
    defaultZoom: 8,
    gemeenteZoom: 15,
    gemeente: null,
    // defaultZoom: 14,
  }

  provincies = L.layerGroup();
  gemeentes = L.layerGroup();
  ongevallen = L.layerGroup();;
  currentZoom = -1

  constructor(config) {
    for(var key in config) 
      if (this.config.hasOwnProperty(key)) this.config[key] = config[key];
    

    this.layer = this.makeLayer();
    this.makeMap()
    
  }

  makeMap() {
    var tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: this.config.maxZoom,
      minZoom: this.config.minZoom,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, productie <a href="https://twitter.com/markuitheiloo">@markuitheiloo</a>'
    });
    this.map = L.map(this.config.nodeId, {
      center: this.config.center, 
      zoom: this.config.defaultZoom, 
      layers: [tiles]}
    );
    var self = this
    this.map.on('moveend', function() {self.updateMarkers(self)})
    window.onhashchange = function(e) {self.locationHashChanged(self, e)};
    //check if user requested a placename:
    if (!this.loadGemeente()) this.updateMarkers(self)
  }

  loadGemeente()
  {
    if (window.location.hash.match(/gemeente=(.+)/)) {
      let gme_naam = /gemeente=(.+)/.exec(window.location.hash)[1].toLowerCase();
      fetch('./api/?GME=' + gme_naam)
        .then(response => response.json())
        .then(points => {
          var bounds = L.latLngBounds(L.latLng(points[0][0], points[0][1]), L.latLng(points[0][0], points[0][1]));
          points.forEach(point => bounds.extend(L.latLng(point[0], point[1])));
          this.map.fitBounds(bounds)
        })
        .catch(e => alert(`plaatsnaam '${gme_naam}' niet gevonden`));
        return true;
    } else {
      return false;
    }
  }

  locationHashChanged(e, self) 
  {
    if ( e.oldURL != e.newURL) self.loadGemeente()
  }

  updateMarkers(self) {
    var zoom = self.map.getZoom();
    // if (zoom == self.currentZoom && zoom < 12) return;
    this.clearMarkers()

    if (zoom < 10) self.makeProvincies();
    else if (zoom < 12) self.makeGemeentes();
    else self.maakOngevallen();

    self.currentZoom = zoom;
  }

  makeLayer() {
    return L.markerClusterGroup({
      // iconCreateFunction: iconCreateFunction,
      chunkedLoading: true
      });
  }

  clearMarkers() {
    this.map.removeLayer(this.gemeentes);
    this.map.removeLayer(this.provincies);
    this.map.removeLayer(this.ongevallen);
  }

  makeFlag(url, mode) {
    let iconSize = [
      50 / (mode == 'GME'?2:1), 
      33 / (mode == 'GME'?2:1)
    ]

    return L.icon({
      iconUrl: `./api/vlag.php?mode=${mode}&url=${url}`,
      shadowUrl: 'assets/flag-shadow.png',

      iconSize:     iconSize,
      shadowSize:   iconSize,
      iconAnchor:   [25, 15], // point of the icon which will correspond to marker's location
      shadowAnchor: [20, 10],  // the same for the shadow
      popupAnchor:  [0, -15] // point from which the popup should open relative to the iconAnchor
    });
  }

  async load(mode) {
    this.loading()
    let url=new URL(`${window.location.protocol}/${window.location.host}${window.location.pathname}/api`);
    let payload = {
      mode: mode,
      bounds: this.map.getBounds(),
      zoom: this.map.getZoom()
    }
    url.searchParams.append('bounds', JSON.stringify(payload))

    return fetch(url).then(response => response.json())
    .catch(e => {
      alert('Er is iets fout gegaan bij het ophalen van de data.')
    });
  }

  makeProvincies() {
    if (this.provincies.getLayers().length == 0) {
      this.load("PVE").then(markers => {
          let statistieken = {}
          markers.forEach(m => {
            var marker = L.marker([m.lat,  m.lng], {icon: this.makeFlag(m.vlag, 'PVE')});
            statistieken[m.naam] = `<p>Provincie <b>${m.naam}</b>:</p><hr>${this.getStatsHtml(m)}`;
            marker.bindPopup(statistieken[m.naam])
            this.provincies.addLayer(marker)
          });
          this.provincies.addTo(this.map)
          fetch('./assets/provinces.geojson')
          .then(response => response.json())
          .then(geojsonFeature => L.geoJSON(geojsonFeature, {
            onEachFeature: (feature, layer) => {
            layer.bindPopup(statistieken[feature.properties.name]);
            }
          }).setStyle({fillOpacity: 0}).addTo(this.provincies))
        })
    } else {
      this.provincies.addTo(this.map)
    }
    this.mode = 'provincies';
  }

  makeGemeentes() {
    if (this.gemeentes.getLayers().length == 0) {
      this.load("GME").then(markers => {
          markers.forEach(m => {
            if (m.vlag) {
              var marker = L.marker([m.lat, m.lng], {icon: this.makeFlag(m.vlag, 'GME')});
            } else {
              var marker = L.marker([m.lat, m.lng]);
            }
            marker.bindPopup(`<p>Gemeente <b>${m.naam}</b>:</p><hr>${this.getStatsHtml(m)}`)
            this.gemeentes.addLayer(marker)
            marker.addTo(this.map)
          });
          this.gemeentes.addTo(this.map)
        })
        .catch(e => {
          alert('Er is iets fout gegaan bij het ophalen van de data.')
        });
    } else {
      this.gemeentes.addTo(this.map)
    }
    this.mode = 'gemeentes';
  }

  makeOngevalIcon(count) {
    return L.icon({
      iconUrl: count == 1 ? `assets/ongeval.png` : `assets/ongevallen.png`,
      iconSize:     [33, 62], // size of the icon
      iconAnchor:   [16, 62], // point of the icon which will correspond to marker's location
      popupAnchor:  [-16, 0] // point from which the popup should open relative to the iconAnchor
    });
  }

  maakOngevallen() {
    this.ongevallen = L.markerClusterGroup({
      iconCreateFunction: this.createOngevallenClusterIcon,
      chunkedLoading: true,
      spiderLegPolylineOptions: {weight: 1.5, color: 'Red', opacity: 1.0}
    });

    let iconOngeval = this.makeOngevalIcon(1)
    let iconOngevallen = this.makeOngevalIcon(2)

    this.load("ONG").then(markers => {
      markers.forEach(m => {
        var marker = L.marker([m.lat, m.lng], {icon: m.count == 1 ? iconOngeval : iconOngevallen});
        marker.bindPopup(this.getStatsHtml(m));
        marker.ongevallen = m.count
        this.ongevallen.addLayer(marker);
      });
      this.map.addLayer(this.ongevallen);
    })
    .catch(() => alert('Er is iets fout gegaan bij het ophalen van de data.')) 
  }

  getStatsHtml(m) {
    var count = new Intl.NumberFormat('nl-NL').format(m.count);
    var countDOD = new Intl.NumberFormat('nl-NL').format(m.DOD);
    var countLET = new Intl.NumberFormat('nl-NL').format(m.LET);
    var countUMS = new Intl.NumberFormat('nl-NL').format(m.UMS);

    let plOngeval = 'ongeval' + (m.count == 1 ? '' : 'len')
    if (m.count == m.DOD) {
      return `<p><b>${countDOD}</b> ${plOngeval} met dodelijke afloop</p>`
    } else if (m.count == m.LET) {
      return `<p><b>${countLET}</b> ${plOngeval} met letsel</p>`
    } else if (m.count == m.UMS) {
      return `<p><b>${countUMS}</b> ${plOngeval} met uitsluitend materiëlele schade</p>`
    } else {
      let oorzaken = [];
      if (m.DOD > 0) oorzaken.push(`<b>${countDOD}</b> met dodelijke afloop`)
      if (m.LET > 0) oorzaken.push(`<b>${countLET}</b> met letsel`)
      if (m.UMS > 0) oorzaken.push(`<b>${countUMS}</b> met uitsluitend materiëlele schade`)
      let laatsteOorzaak = oorzaken.pop()
      return `<p><b>${count}</b> ${plOngeval}, waarvan ${oorzaken.join(', ')} en ${laatsteOorzaak}.</p>`
    }
  }

  loading()
  {
    // console.debug("@TODO: loading indicator");
  }

  createOngevallenClusterIcon (cluster)
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

}

