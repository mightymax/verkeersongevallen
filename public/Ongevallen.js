class Ongevallen 
{
  config = {
    nodeId: 'map',
    center: L.latLng(52.25, 5.75), //Nederland
    // center: L.latLng(52.6011, 4.7019), //Heiloo
    minZoom: 8,
    maxZoom: 18,
    defaultZoom: 8,
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
    this.map = L.map(this.config.nodeId, {center: this.config.center, zoom: this.config.defaultZoom, layers: [tiles]});
    var self = this
    this.map.on('moveend', function() {self.updateMarkers(self)})
    this.updateMarkers(self)
    
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

  makeFlagIconProvincie(iconUrl) {
    return L.icon({
      iconUrl: iconUrl,
      shadowUrl: 'assets/flag-shadow.png',

      iconSize:     [50, 33], // size of the icon
      shadowSize:   [50, 33], // size of the shadow
      iconAnchor:   [25, 15], // point of the icon which will correspond to marker's location
      shadowAnchor: [20, 10],  // the same for the shadow
      popupAnchor:  [0, -15] // point from which the popup should open relative to the iconAnchor
    });
  }

  makeFlagIconGemeente(iconUrl) {
    return L.icon({
      iconUrl: iconUrl,
      shadowUrl: 'assets/flag-shadow.png',
      iconSize:     [25, 16], // size of the icon
      shadowSize:   [25, 16], // size of the shadow
      shadowAnchor: [20, 10],  // the same for the shadow
      iconAnchor:   [25, 15], // point of the icon which will correspond to marker's location
      popupAnchor:  [0, -15] // point from which the popup should open relative to the iconAnchor
    });
  }

  makeProvincies() {
    if (this.provincies.getLayers().length == 0) {
      this.loading()
      fetch('./api?PVE', {method: 'POST',body: JSON.stringify({zoom: this.map.getZoom(), bounds: this.map.getBounds()})})
        .then(response => response.json())
        .then(markers => {
          let statistieken = {}
          markers.forEach(m => {
            var marker = L.marker([m.lat,  m.lng], {icon: this.makeFlagIconProvincie(m.vlag)});
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
        .catch(e => {
          alert('Er is iets fout gegaan bij het ophalen van de data.')
        });
    } else {
      this.provincies.addTo(this.map)
    }

    this.mode = 'provincies';
  }

  makeGemeentes() {
    if (true || this.gemeentes.getLayers().length == 0) {
      this.loading()
      fetch('./api?GME', {method: 'POST',body: JSON.stringify({zoom: this.map.getZoom(), bounds: this.map.getBounds()})})
        .then(response => response.json())
        .then(markers => {
          markers.forEach(m => {
            if (m.vlag) {
              var marker = L.marker([m.lat, m.lng], {icon: this.makeFlagIconGemeente(m.vlag)});
            } else {
              var marker = L.marker([m.lat, m.lng]);
            }
            marker.bindPopup(`<p>Gemeente <b>${m.GME_NAAM}</b>:</p><hr>${this.getStatsHtml(m)}`)
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

    fetch('./api/?ONG', {method: 'POST',body: JSON.stringify({zoom: this.map.getZoom(), bounds: this.map.getBounds()})})
    .then (response => response.json())
    .then(markers => {
      markers.forEach(m => {
        console.log(m);
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

