<html>
    <head>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
        integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
        crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
        integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
        crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

        <style>
            #map { height: 500px; }
            .marker-entry {
                white-space: nowrap;
                text-align: center;
                display: inline-block;
                text-align: center;
                position: relative;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -100%);
            }
            .marker-entry::after {
                content: '';
                display: inline-block;
                width: 0;
                height: 0;
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-top: 5px solid #f00;
            }
            .marker-entry > .marker-text {
                border: 1px solid black;
                text-decoration: none;
                background: red;
                border-radius: 3px;
                padding: 2px 4px;
                color: white;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div id="map"></div>
        <script>
            var map = L.map('map').setView([44.4377401, 26.0945919], 12);
            L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoiZmxvcmluMTY5MyIsImEiOiJja3dtNjNmN2cwNWdyMnFxdDZieGd6ZzV5In0.C3R3FJH7i8aLChJlZFA6MQ', {
                attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
                maxZoom: 18,
                id: 'mapbox/streets-v11',
                tileSize: 512,
                zoomOffset: -1,
                accessToken: 'pk.eyJ1IjoiZmxvcmluMTY5MyIsImEiOiJja3dtNjNmN2cwNWdyMnFxdDZieGd6ZzV5In0.C3R3FJH7i8aLChJlZFA6MQ'
            }).addTo(map);

            // let marker = new L.Marker([44.4377401, 26.0945919], {
            //     icon: new L.DivIcon({
            //         className: '',
            //         html: '<div class="marker-entry"><a class="marker-text" href="" target="_blank">300 Euro</a><br></div>'
            //     })
            // });
            // marker.addTo(map);
            // L.marker([44.4377401, 26.0945919]).addTo(map)
            // .bindPopup('A pretty CSS3 popup.<br> Easily customizable.')
            // .openPopup();

            axios.get('/markers')
                .then(res => {
                    res.data.forEach(m => {
                        let marker = new L.Marker([m.lat, m.lon], {
                            icon: new L.DivIcon({
                                className: '',
                                html: `<div class="marker-entry"><a class="marker-text" href="${m.url}" target="_blank">${m.text_price}</a><br></div>`
                            })
                        });
                        marker.addTo(map);
                    });
                });
        </script>
    </body>
</html>
