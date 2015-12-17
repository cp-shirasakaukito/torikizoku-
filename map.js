
var util = require('util');

var gmAPI = new googleMapsAPI();
markers = [
    { 'location': '東京都台東区浅草' },
    { 'location': '東京都台東区上野' },
    { 'location': '東京都台東区東上野',
        'color': 'red',
        'label': 'A',
        'shadow': 'false',
        'icon' : 'http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=cafe%7C996600'
    }
];

util.puts(gm.staticMap('東京都台東区', 14, '2000x800', false, false, 'roadmap', markers));